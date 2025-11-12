DELIMITER $$
DROP PROCEDURE IF EXISTS `radius`.`C41`$$
CREATE  PROCEDURE `radius`.`C41`(dat_beg DATE,dat_end DATE)
BEGIN
DECLARE cnt_bank, cnt_noff, cnt_trans, cnt_tot, rid INT(11) DEFAULT 0;
DECLARE sum_bank, sum_noff, sum_trans, tot_sum DECIMAL(10,2) DEFAULT 0.0;
DECLARE res_name CHAR(128) CHARACTER SET utf8;
DECLARE done BOOLEAN DEFAULT 0;
DECLARE cur1 CURSOR FOR SELECT id, longname FROM `radius`.`user` WHERE name like 'rs%';
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
drop table if EXISTS `rtemp`;
create temporary table `rtemp` (`rid` int not null, res_name char(128) character set utf8, `cnt_bank` int(11),`sum_bank` DECIMAL(10,2),
`cnt_noff` int(11),`sum_noff` DECIMAL(10,2),`cnt_trans` int(11),`sum_trans` DECIMAL(10,2),`cnt_tot` int(11),`tot_sum` DECIMAL(10,2)) character set utf8;
OPEN cur1;
read_loop: LOOP
FETCH cur1 INTO rid,res_name;
    IF done THEN
      LEAVE read_loop;
    END IF;
CALL C31(rid,dat_beg,dat_end, cnt_bank,sum_bank, cnt_noff, sum_noff, cnt_trans, sum_trans, cnt_tot, tot_sum);
insert into rtemp values(rid,res_name,cnt_bank,sum_bank, cnt_noff, sum_noff, cnt_trans, sum_trans, cnt_tot, tot_sum);
END LOOP;
CLOSE cur1;
select * from rtemp where rid >=0;
    END$$
DELIMITER ;