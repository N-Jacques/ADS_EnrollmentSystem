-- write to phpmyadmin view first for php to make a table for enlistment
CREATE OR REPLACE VIEW vw_enlisted_schedule AS
SELECT 
    e.student_id,
    e.sem_id,
    es.sub_code,
    s.title AS subject_title,
    es.section,
    CONCAT(
        d.day_id, ' ',
        sch.time_start, '–', sch.time_end
    ) AS schedule_detail
FROM enlistment e
JOIN enlisted_subjects es 
    ON e.enlistment_id = es.enlistment_id
JOIN subjects s 
    ON es.sub_code = s.sub_code
LEFT JOIN schedule sch 
    ON sch.sub_code = es.sub_code 
    AND sch.sem_id = e.sem_id
    AND sch.section = es.section
LEFT JOIN day_details d 
    ON d.day_id = sch.day_id
ORDER BY e.student_id, es.sub_code, sch.section;


