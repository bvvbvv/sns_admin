DELIMITER $$
DROP PROCEDURE IF EXISTS `radius`.`C42`$$
CREATE  PROCEDURE `radius`.`C42`(dat_beg DATE,dat_end DATE)
BEGIN
DECLARE cnt_bank, cnt_local, cnt_ekvar, cnt_tot, rid INT(11) DEFAULT 0;
DECLARE sum_bank, sum_local, sum_ekvar, tot_sum DECIMAL(10,2) DEFAULT 0.0;
DECLARE res_name CHAR(128) CHARACTER SET utf8;
DECLARE done BOOLEAN DEFAULT 0;
DECLARE cur1 CURSOR FOR SELECT id, longname FROM `radius`.`user` WHERE name like 'rs_damansk' or name like 'rs_pshichenko' or name like 'rs_isaoptik' or name like 'rs_isaenko';
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
drop table if EXISTS `rtemp`;
create temporary table `rtemp` (`rid` int not null, res_name char(128), `cnt_bank` int(11),`sum_bank` DECIMAL(10,2),
`cnt_local` int(11),`sum_local` DECIMAL(10,2),`cnt_ekvar` int(11),`sum_ekvar` DECIMAL(10,2),`cnt_tot` int(11),`tot_sum` DECIMAL(10,2));
OPEN cur1;
read_loop: LOOP
FETCH cur1 INTO rid,res_name;
    IF done THEN
      LEAVE read_loop;
    END IF;
CALL C32(rid,dat_beg,dat_end, cnt_bank,sum_bank, cnt_local, sum_local, cnt_ekvar, sum_ekvar, cnt_tot, tot_sum);
insert into rtemp values(rid,res_name,cnt_bank,sum_bank, cnt_local, sum_local, cnt_ekvar, sum_ekvar, cnt_tot, tot_sum);
END LOOP;
CLOSE cur1;
select * from rtemp where rid >=0;
    END$$
DELIMITER ;