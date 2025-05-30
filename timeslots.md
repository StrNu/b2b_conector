El sistema de horarios y time slots de este proyecto es una solución completa para gestionar los horarios de encuentros B2B en eventos. Te explicaré cómo funciona:
1. Conceptos Básicos
Event Schedule: Representa un día del evento con su horario general
Time Slot: Una franja horaria específica dentro del día del evento
Table: Mesa donde se realizan las reuniones (cada time slot se multiplica por el número de mesas)
Break: Período de descanso donde no se programan reuniones
2. Estructura de Datos
En la base de datos, esto se representa así:
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
3. Proceso de Generación de Horarios
El flujo completo funciona así:
Definición de parámetros del evento:
Fechas del evento (start_date, end_date)
Horario diario (start_time, end_time)
Duración de reuniones (meeting_duration)
Número de mesas (available_tables)
Descansos (breaks)
Generación matemática de slots:

Se calcula cuántos slots caben en un día: (end_time - start_time) / meeting_duration
Se generan los horarios específicos (8:00-8:30, 8:30-9:00, etc.)
Se eliminan los slots que coinciden con breaks
Se multiplican por el número de mesas disponibles
Almacenamiento en base de datos:
Cada slot se vincula a un día del evento
4. Proceso de Asignación de Reuniones
Cuando se desea programar una reunión:
Verificación de disponibilidad:
Se buscan días comunes donde ambas empresas asisten
Se revisan los slots disponibles en esos días (is_available = true)
Se verifica que no haya conflictos (mismo horario para alguna de las dos empresas)
Asignación:
Se asigna un match_id al slot seleccionado
Se marca como no disponible (is_available = false)
Se actualiza el estado del match a "scheduled"
5. Herramientas de Gestión
El sistema proporciona varias herramientas administrativas:
Vista de calendario: Para explorar los horarios por día
Recálculo de horarios: Si se modifican los parámetros del evento
Asignación manual: Para casos especiales o resolución de conflictos
Auto-programación: Para asignar automáticamente todos los matches
6. Implementación Técnica
Generación de Time Slots
function generateTimeSlots(startTime, endTime, meetingDuration, breaks) {
  const slots = [];
  let currentMinute = parseTimeToMinutes(startTime);
  const endMinutes = parseTimeToMinutes(endTime);

  while (currentMinute + meetingDuration <= endMinutes) {
    const slotEnd = currentMinute + meetingDuration;    
    // Verificar si coincide con algún break
    const overlapsWithBreak = checkBreakOverlap(currentMinute, slotEnd, breaks);
  
    if (!overlapsWithBreak) {
      slots.push({
        start_time: formatMinutesToTime(currentMinute),
        end_time: formatMinutesToTime(slotEnd)
      });
    }
    
    currentMinute += meetingDuration;
  }
  
  return slots;
}
Lógica de Base de Datos
El sistema utiliza funciones SQL para operaciones complejas:

regenerate_event_schedules: Recalcula todos los horarios de un evento
get_event_time_slots: Obtiene los slots para una fecha específica
get_common_attendance_days: Encuentra días donde ambas empresas asisten
auto_schedule_matches: Automatiza la asignación de reuniones
Hooks de React
Se utilizan varios hooks para gestionar la lógica:

useScheduleData: Coordina la obtención y gestión de datos de horarios
useScheduleRecalculation: Maneja la recalculación de horarios
useScheduleExistence: Verifica si existen horarios generados
7. Visualización
Los componentes de UI muestran los datos de forma organizada:

SchedulesTab: Vista general de horarios
ScheduleTable: Tabla detallada de slots disponibles
DateNavigator: Permite navegar entre fechas
ScheduleBreakdownModal: Muestra desglose detallado de horarios
8. Algoritmo de Asignación Automática
La lógica de asignación automática (auto_schedule_matches):

Prioriza matches por match_strength (más alto primero)
Para cada match busca días comunes de asistencia
En cada día, busca slots disponibles
Verifica que no haya conflictos (mismo horario para algún participante)
Asigna el match al primer slot disponible sin conflictos
Este sistema completo permite gestionar eficientemente los horarios de reuniones en eventos de networking B2B, optimizando la logística y asegurando que las empresas se reúnan con sus contrapartes más relevantes.
