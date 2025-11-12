DELIMITER $$
DROP PROCEDURE IF EXISTS `radius`.`C32`$$
CREATE    PROCEDURE `radius`.`C32`(IN r_id INT, dat_beg DATE, dat_end DATE, 
OUT cnt_bank INT(11), OUT sum_bank DECIMAL(10,2),OUT cnt_local INT(11), OUT sum_local DECIMAL(10,2),
OUT cnt_ekvar INT(11), OUT sum_ekvar DECIMAL(10,2),OUT cnt_tot INT(11), OUT tot_sum DECIMAL(10,2))
BEGIN 	
        SELECT  COUNT(*), SUM(ROUND(transaction.credit /100, 2)) INTO cnt_bank, sum_bank FROM transaction
WHERE transaction.debtor  IN (SELECT id FROM user WHERE parentid=r_id)
AND transaction.datepay >=dat_beg AND transaction.datepay <=dat_end  AND transaction.typepay = 'bank';
	SELECT  COUNT(*), SUM(ROUND(transaction.credit /100, 2)) INTO cnt_local, sum_local FROM transaction
WHERE transaction.debtor  IN (SELECT id FROM user WHERE parentid=r_id)
AND transaction.datepay >=dat_beg AND transaction.datepay <=dat_end  AND transaction.typepay = 'local';
	SELECT  COUNT(*), SUM(ROUND(transaction.credit /100, 2)) INTO cnt_ekvar, sum_ekvar FROM transaction
WHERE transaction.debtor  IN (SELECT id FROM user WHERE parentid=r_id)
AND transaction.datepay >=dat_beg AND transaction.datepay <=dat_end  AND transaction.typepay = 'ekvar';
SELECT (IFNULL(sum_local,0)) into sum_local;
SELECT (IFNULL(sum_bank,0)) into sum_bank;
SELECT (IFNULL(sum_ekvar,0)) into sum_ekvar;
SELECT sum_local +sum_bank +sum_ekvar INTO tot_sum;
SELECT (IFNULL(cnt_local,0) +IFNULL(cnt_bank,0) +IFNULL(cnt_ekvar,0) ) INTO cnt_tot;

END$$
DELIMITER ;