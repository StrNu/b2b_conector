Existe una tabla que se llama “matches” que almacena los matches entre compradores y proveedores y los días que asiste cada comprador o proveedor y existe una vista que es “time_slots” que está en esta en la  ruta: “events/time_slots/{event_id}”, genera una estrategia para poder obtener esa información para poder generar la cita, se tomará en cuenta el registro de match, el día en que coinciden un comprador y proveedor y los horarios disponibles  en la tabla  “time_slots”, comencemos tomando el primero disponible, como la tabla time slots es virtual se me ocurre que puede ser una tabla en json que vaya marcando que slots ya ocupamos,  de cualquier forma espero tu recomendación experta para solucionar la generación de citas. Por el momento se van a generar desde el botón “programar” en la vista matches, para generar una a una, más adelante trabajaremos para generar todas al mismo tiempo. por el momento ese botón genera el link a /events/schedules/{event_id}, una vez con la información obtenida, generaremos el registro en la tabla event_schedules, te muestro su estructura:

describe event_schedules;
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
+----------------+-------------+------+-----+---------+----------------+
8 rows in set (0,001 sec)

por favor dime si tienes alguna duda antes de continuar.
