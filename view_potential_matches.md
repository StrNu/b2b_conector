SELECT
    b.company_id AS buyer_id,
    b.company_name AS buyer_name,
    s.company_id AS supplier_id,
    s.company_name AS supplier_name,
    b.event_id AS event_id,
    json_arrayagg(DISTINCT r.event_subcategory_id) AS buyer_subcategories,
    json_arrayagg(DISTINCT o.event_subcategory_id) AS supplier_subcategories,
    group_concat(DISTINCT bad.attendance_date SEPARATOR ",") AS buyer_dates,
    group_concat(DISTINCT sad.attendance_date SEPARATOR ",") AS supplier_dates,
    NULL AS categories, -- No category match for this reason
    b.keywords AS buyer_keywords,
    s.keywords AS supplier_keywords,
    b.description AS buyer_description,
    s.description AS supplier_description,
    'subcategoria_sin_dias_comunes' AS reason,
    NULL AS keywords_match,
    COALESCE(
        (SELECT group_concat(DISTINCT ad1.attendance_date SEPARATOR ",")
         FROM b2b_conector.attendance_days ad1
         JOIN b2b_conector.attendance_days ad2 ON ad1.attendance_date = ad2.attendance_date
         WHERE ad1.company_id = b.company_id
         AND ad2.company_id = s.company_id
         AND ad1.event_id = b.event_id
         AND ad2.event_id = b.event_id),
        '0'
    ) AS coincidence_of_dates
FROM
    b2b_conector.company b
JOIN
    b2b_conector.requirements r ON b.company_id = r.buyer_id
JOIN
    b2b_conector.event_subcategories esc ON r.event_subcategory_id = esc.event_subcategory_id
JOIN
    b2b_conector.company s ON s.event_id = b.event_id AND s.role = 'supplier'
JOIN
    b2b_conector.supplier_offers o ON o.supplier_id = s.company_id
LEFT JOIN
    b2b_conector.attendance_days bad ON bad.company_id = b.company_id AND bad.event_id = b.event_id
LEFT JOIN
    b2b_conector.attendance_days sad ON sad.company_id = s.company_id AND sad.event_id = b.event_id
WHERE
    b.role = 'buyer'
    AND b.company_id <> s.company_id
    AND r.event_subcategory_id = o.event_subcategory_id
    AND NOT EXISTS (
        SELECT 1
        FROM b2b_conector.attendance_days ad1
        JOIN b2b_conector.attendance_days ad2 ON ad1.attendance_date = ad2.attendance_date
        WHERE ad1.company_id = b.company_id
        AND ad2.company_id = s.company_id
        AND ad1.event_id = b.event_id
        AND ad2.event_id = b.event_id
        LIMIT 1
    )
GROUP BY
    b.company_id, s.company_id, b.event_id

UNION ALL

-- Description match
SELECT
    b.company_id AS buyer_id,
    b.company_name AS buyer_name,
    s.company_id AS supplier_id,
    s.company_name AS supplier_name,
    b.event_id AS event_id,
    NULL AS buyer_subcategories,
    NULL AS supplier_subcategories,
    group_concat(DISTINCT bad.attendance_date SEPARATOR ",") AS buyer_dates,
    group_concat(DISTINCT sad.attendance_date SEPARATOR ",") AS supplier_dates,
    NULL AS categories,
    b.keywords AS buyer_keywords,
    s.keywords AS supplier_keywords,
    b.description AS buyer_description,
    s.description AS supplier_description,
    'descripcion' AS reason,
    NULL AS keywords_match,
    COALESCE(
        (SELECT group_concat(DISTINCT ad1.attendance_date SEPARATOR ",")
         FROM b2b_conector.attendance_days ad1
         JOIN b2b_conector.attendance_days ad2 ON ad1.attendance_date = ad2.attendance_date
         WHERE ad1.company_id = b.company_id
         AND ad2.company_id = s.company_id
         AND ad1.event_id = b.event_id
         AND ad2.event_id = b.event_id),
        '0'
    ) AS coincidence_of_dates
FROM
    b2b_conector.company b
JOIN
    b2b_conector.company s ON s.event_id = b.event_id AND s.role = 'supplier'
LEFT JOIN
    b2b_conector.attendance_days bad ON bad.company_id = b.company_id AND bad.event_id = b.event_id
LEFT JOIN
    b2b_conector.attendance_days sad ON sad.company_id = s.company_id AND sad.event_id = b.event_id
WHERE
    b.role = 'buyer'
    AND b.company_id <> s.company_id
    AND b.description IS NOT NULL
    AND s.description IS NOT NULL
    AND (
        LOCATE(LCASE(s.description), LCASE(b.description)) > 0
        OR LOCATE(LCASE(b.description), LCASE(s.description)) > 0
    )
GROUP BY
    b.company_id, s.company_id, b.event_id

UNION ALL

-- Keyword match
SELECT
    b.company_id AS buyer_id,
    b.company_name AS buyer_name,
    s.company_id AS supplier_id,
    s.company_name AS supplier_name,
    b.event_id AS event_id,
    NULL AS buyer_subcategories,
    NULL AS supplier_subcategories,
    group_concat(DISTINCT bad.attendance_date SEPARATOR ",") AS buyer_dates,
    group_concat(DISTINCT sad.attendance_date SEPARATOR ",") AS supplier_dates,
    NULL AS categories,
    b.keywords AS buyer_keywords,
    s.keywords AS supplier_keywords,
    b.description AS buyer_description,
    s.description AS supplier_description,
    'palabras_clave' AS reason,
    (SELECT json_arrayagg(DISTINCT json_unquote(jb.value))
     FROM (JSON_TABLE(b.keywords, '$[*]' COLUMNS (`value` varchar(255) PATH '$')) jb
           JOIN JSON_TABLE(s.keywords, '$[*]' COLUMNS (`value` varchar(255) PATH '$')) js ON jb.value = js.value)) AS keywords_match,
    COALESCE(
        (SELECT group_concat(DISTINCT ad1.attendance_date SEPARATOR ",")
         FROM b2b_conector.attendance_days ad1
         JOIN b2b_conector.attendance_days ad2 ON ad1.attendance_date = ad2.attendance_date
         WHERE ad1.company_id = b.company_id
         AND ad2.company_id = s.company_id
         AND ad1.event_id = b.event_id
         AND ad2.event_id = b.event_id),
        '0'
    ) AS coincidence_of_dates
FROM
    b2b_conector.company b
JOIN
    b2b_conector.company s ON s.event_id = b.event_id AND s.role = 'supplier'
LEFT JOIN
    b2b_conector.attendance_days bad ON bad.company_id = b.company_id AND bad.event_id = b.event_id
LEFT JOIN
    b2b_conector.attendance_days sad ON sad.company_id = s.company_id AND sad.event_id = b.event_id
WHERE
    b.role = 'buyer'
    AND b.company_id <> s.company_id
    AND json_overlaps(b.keywords, s.keywords)
GROUP BY
    b.company_id, s.company_id, b.event_id;