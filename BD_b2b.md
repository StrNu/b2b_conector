describe assistants;
+--------------+--------------+------+-----+---------+----------------+
| Field        | Type         | Null | Key | Default | Extra          |
+--------------+--------------+------+-----+---------+----------------+
| assistant_id | int(11)      | NO   | PRI | NULL    | auto_increment |
| company_id   | int(11)      | YES  | MUL | NULL    |                |
| first_name   | varchar(255) | YES  |     | NULL    |                |
| last_name    | varchar(255) | YES  |     | NULL    |                |
| mobile_phone | varchar(20)  | YES  |     | NULL    |                |
| email        | varchar(255) | YES  |     | NULL    |                |
+--------------+--------------+------+-----+---------+----------------+
6 rows in set (0,001 sec)

MariaDB [b2b_conector]> describe attendance_days;
+-----------------+---------+------+-----+---------+----------------+
| Field           | Type    | Null | Key | Default | Extra          |
+-----------------+---------+------+-----+---------+----------------+
| attendance_id   | int(11) | NO   | PRI | NULL    | auto_increment |
| company_id      | int(11) | YES  | MUL | NULL    |                |
| event_id        | int(11) | YES  | MUL | NULL    |                |
| attendance_date | date    | YES  |     | NULL    |                |
+-----------------+---------+------+-----+---------+----------------+
4 rows in set (0,001 sec)

MariaDB [b2b_conector]> describe breaks;
+------------+---------+------+-----+---------+----------------+
| Field      | Type    | Null | Key | Default | Extra          |
+------------+---------+------+-----+---------+----------------+
| break_id   | int(11) | NO   | PRI | NULL    | auto_increment |
| event_id   | int(11) | YES  | MUL | NULL    |                |
| start_time | time    | YES  |     | NULL    |                |
| end_time   | time    | YES  |     | NULL    |                |
+------------+---------+------+-----+---------+----------------+
4 rows in set (0,001 sec)

MariaDB [b2b_conector]> describe event_categories;
+-------------------+--------------+------+-----+---------+----------------+
| Field             | Type         | Null | Key | Default | Extra          |
+-------------------+--------------+------+-----+---------+----------------+
| event_category_id | int(11)      | NO   | PRI | NULL    | auto_increment |
| event_id          | int(11)      | NO   | MUL | NULL    |                |
| category_id       | int(11)      | YES  | MUL | NULL    |                |
| name              | varchar(255) | NO   |     | NULL    |                |
| is_active         | tinyint(1)   | YES  |     | 1       |                |
+-------------------+--------------+------+-----+---------+----------------+
5 rows in set (0,004 sec)

MariaDB [b2b_conector]> describe event_subcategories;
+----------------------+--------------+------+-----+---------+----------------+
| Field                | Type         | Null | Key | Default | Extra          |
+----------------------+--------------+------+-----+---------+----------------+
| event_subcategory_id | int(11)      | NO   | PRI | NULL    | auto_increment |
| event_category_id    | int(11)      | NO   | MUL | NULL    |                |
| subcategory_id       | int(11)      | YES  | MUL | NULL    |                |
| name                 | varchar(255) | NO   |     | NULL    |                |
| is_active            | tinyint(1)   | YES  |     | 1       |                |
+----------------------+--------------+------+-----+---------+----------------+
5 rows in set (0,001 sec)

MariaDB [b2b_conector]> describe event_schedules;
+----------------+-------------+------+-----+---------+----------------+
| Field          | Type        | Null | Key | Default | Extra          |
+----------------+-------------+------+-----+---------+----------------+
| schedule_id    | int(11)     | NO   | PRI | NULL    | auto_increment |
| event_id       | int(11)     | YES  | MUL | NULL    |                |
| match_id       | int(11)     | YES  | MUL | NULL    |                |
| table_number   | int(11)     | YES  |     | NULL    |                |
| start_datetime | datetime    | YES  |     | NULL    |                |
| end_datetime   | datetime    | YES  |     | NULL    |                |
| status         | varchar(30) | YES  |     | NULL    |                |
| is_manual      | tinyint(1)  | YES  |     | 0       |                |

describe event_statistics;
+---------------+-----------+------+-----+---------------------+----------------+
| Field         | Type      | Null | Key | Default             | Extra          |
+---------------+-----------+------+-----+---------------------+----------------+
| id            | int(11)   | NO   | PRI | NULL                | auto_increment |
| event_id      | int(11)   | NO   | UNI | NULL                |                |
| keywords      | longtext  | YES  |     | NULL                |                |
| categories    | longtext  | YES  |     | NULL                |                |
| subcategories | longtext  | YES  |     | NULL                |                |
| descriptions  | longtext  | YES  |     | NULL                |                |
| created_at    | timestamp | YES  |     | current_timestamp() |                |
| updated_at    | timestamp | YES  |     | NULL                |                |
+---------------+-----------+------+-----+---------------------+----------------+
8 rows in set (0,001 sec)

MariaDB [b2b_conector]> describe event_users;
+------------+--------------+------+-----+---------------------+----------------+
| Field      | Type         | Null | Key | Default             | Extra          |
+------------+--------------+------+-----+---------------------+----------------+
| id         | int(11)      | NO   | PRI | NULL                | auto_increment |
| company_id | int(11)      | YES  | MUL | NULL                |                |
| event_id   | int(11)      | YES  | MUL | NULL                |                |
| role       | varchar(50)  | YES  |     | NULL                |                |
| is_active  | tinyint(1)   | YES  |     | NULL                |                |
| created_at | timestamp    | YES  |     | current_timestamp() |                |
| email      | varchar(255) | YES  |     | NULL                |                |
| password   | varchar(255) | YES  |     | NULL                |                |
+------------+--------------+------+-----+---------------------+----------------+
8 rows in set (0,001 sec)

MariaDB [b2b_conector]> describe events;
+------------------+--------------+------+-----+---------+----------------+
| Field            | Type         | Null | Key | Default | Extra          |
+------------------+--------------+------+-----+---------+----------------+
| event_id         | int(11)      | NO   | PRI | NULL    | auto_increment |
| event_name       | varchar(255) | YES  |     | NULL    |                |
| venue            | varchar(255) | YES  |     | NULL    |                |
| start_date       | date         | YES  |     | NULL    |                |
| end_date         | date         | YES  |     | NULL    |                |
| available_tables | int(11)      | YES  |     | NULL    |                |
| meeting_duration | int(11)      | YES  |     | NULL    |                |
| is_active        | tinyint(1)   | YES  |     | NULL    |                |
| start_time       | time         | YES  |     | NULL    |                |
| end_time         | time         | YES  |     | NULL    |                |
| has_break        | tinyint(1)   | YES  |     | NULL    |                |
| company_name     | text         | YES  |     | NULL    |                |
| contact_name     | text         | YES  |     | NULL    |                |
| contact_phone    | text         | YES  |     | NULL    |                |
| contact_email    | text         | YES  |     | NULL    |                |
| company_logo     | varchar(255) | YES  |     | NULL    |                |
| event_logo       | varchar(255) | YES  |     | NULL    |                |
+------------------+--------------+------+-----+---------+----------------+
17 rows in set (0,002 sec)

MariaDB [b2b_conector]> describe matches;
+------------------------+---------------+------+-----+---------------------+----------------+
| Field                  | Type          | Null | Key | Default             | Extra          |
+------------------------+---------------+------+-----+---------------------+----------------+
| match_id               | int(11)       | NO   | PRI | NULL                | auto_increment |
| buyer_id               | int(11)       | YES  | MUL | NULL                |                |
| supplier_id            | int(11)       | YES  | MUL | NULL                |                |
| event_id               | int(11)       | YES  | MUL | NULL                |                |
| match_strength         | decimal(10,0) | YES  |     | NULL                |                |
| created_at             | timestamp     | YES  |     | current_timestamp() |                |
| status                 | varchar(50)   | YES  |     | NULL                |                |
| matched_categories     | longtext      | YES  |     | NULL                |                |
| programed              | tinyint(1)    | NO   |     | 0                   |                |
| match_level            | varchar(32)   | YES  |     | NULL                |                |
| buyer_subcategories    | longtext      | YES  |     | NULL                |                |
| supplier_subcategories | longtext      | YES  |     | NULL                |                |
| buyer_dates            | varchar(255)  | YES  |     | NULL                |                |
| supplier_dates         | varchar(255)  | YES  |     | NULL                |                |
| buyer_keywords         | longtext      | YES  |     | NULL                |                |
| supplier_keywords      | longtext      | YES  |     | NULL                |                |
| buyer_description      | text          | YES  |     | NULL                |                |
| supplier_description   | text          | YES  |     | NULL                |                |
| reason                 | varchar(64)   | YES  |     | NULL                |                |
| keywords_match         | longtext      | YES  |     | NULL                |                |
| coincidence_of_dates   | varchar(255)  | YES  |     | NULL                |                |
+------------------------+---------------+------+-----+---------------------+----------------+
21 rows in set (0,002 sec)

MariaDB [b2b_conector]> describe requirements;
+----------------------+---------------+------+-----+---------------------+----------------+
| Field                | Type          | Null | Key | Default             | Extra          |
+----------------------+---------------+------+-----+---------------------+----------------+
| requirement_id       | int(11)       | NO   | PRI | NULL                | auto_increment |
| buyer_id             | int(11)       | YES  | MUL | NULL                |                |
| unit_of_measurement  | varchar(50)   | YES  |     | NULL                |                |
| event_subcategory_id | int(11)       | NO   | MUL | NULL                |                |
| budget_usd           | decimal(10,0) | YES  |     | NULL                |                |
| quantity             | int(11)       | YES  |     | NULL                |                |
| created_at           | timestamp     | YES  |     | current_timestamp() |                |
+----------------------+---------------+------+-----+---------------------+----------------+
7 rows in set (0,001 sec)

MariaDB [b2b_conector]> describe matches;
+------------------------+---------------+------+-----+---------------------+----------------+
| Field                  | Type          | Null | Key | Default             | Extra          |
+------------------------+---------------+------+-----+---------------------+----------------+
| match_id               | int(11)       | NO   | PRI | NULL                | auto_increment |
| buyer_id               | int(11)       | YES  | MUL | NULL                |                |
| supplier_id            | int(11)       | YES  | MUL | NULL                |                |
| event_id               | int(11)       | YES  | MUL | NULL                |                |
| match_strength         | decimal(10,0) | YES  |     | NULL                |                |
| created_at             | timestamp     | YES  |     | current_timestamp() |                |
| status                 | varchar(50)   | YES  |     | NULL                |                |
| matched_categories     | longtext      | YES  |     | NULL                |                |
| programed              | tinyint(1)    | NO   |     | 0                   |                |
| match_level            | varchar(32)   | YES  |     | NULL                |                |
| buyer_subcategories    | longtext      | YES  |     | NULL                |                |
| supplier_subcategories | longtext      | YES  |     | NULL                |                |
| buyer_dates            | varchar(255)  | YES  |     | NULL                |                |
| supplier_dates         | varchar(255)  | YES  |     | NULL                |                |
| buyer_keywords         | longtext      | YES  |     | NULL                |                |
| supplier_keywords      | longtext      | YES  |     | NULL                |                |
| buyer_description      | text          | YES  |     | NULL                |                |
| supplier_description   | text          | YES  |     | NULL                |                |
| reason                 | varchar(64)   | YES  |     | NULL                |                |
| keywords_match         | longtext      | YES  |     | NULL                |                |
| coincidence_of_dates   | varchar(255)  | YES  |     | NULL                |                |
+------------------------+---------------+------+-----+---------------------+----------------+
21 rows in set (0,004 sec)

MariaDB [b2b_conector]> describe supplier_offers;
+----------------------+---------+------+-----+---------+----------------+
| Field                | Type    | Null | Key | Default | Extra          |
+----------------------+---------+------+-----+---------+----------------+
| offer_id             | int(11) | NO   | PRI | NULL    | auto_increment |
| event_subcategory_id | int(11) | NO   | MUL | NULL    |                |
| supplier_id          | int(11) | NO   | MUL | NULL    |                |
+----------------------+---------+------+-----+---------+----------------+
3 rows in set (0,001 sec)

MariaDB [b2b_conector]> describe users;
+-------------------+--------------+------+-----+---------------------+----------------+
| Field             | Type         | Null | Key | Default             | Extra          |
+-------------------+--------------+------+-----+---------------------+----------------+
| user_id           | int(11)      | NO   | PRI | NULL                | auto_increment |
| username          | varchar(255) | YES  |     | NULL                |                |
| email             | varchar(255) | YES  |     | NULL                |                |
| password_hash     | varchar(255) | YES  |     | NULL                |                |
| role              | varchar(20)  | YES  |     | NULL                |                |
| is_active         | tinyint(1)   | YES  |     | 1                   |                |
| registration_date | timestamp    | YES  |     | current_timestamp() |                |
| name              | varchar(255) | YES  |     | NULL                |                |
