-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: attendance
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `student_id` varchar(20) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES ('F24113002410','Radhika','5874589621','Mechanical','2','$2y$10$NyiL77LaWhcAT1FALID3Ueo5cltgGQ0VMXhqVixmhlupgN9knbnha'),('F24113007028','Ram','5487854785','Computer Science','5th Semester','$2y$10$lCqQ6d4.AWnmAAjFGgIbPeHIrdGR0cdxHmTPStF7nzYsvP99MaOPG'),('F24113007029','Sushil Kanhar','7735575832','Computer Science','5th Semester','$2y$10$r/IoYUEgxxSoI1VBwVGNKurpBDNbJJY1tAJ2SXKYjOSDvkJxo5sBC'),('F24113007056','Bharat','5847896588','Computer Science','3rd Semester','$2y$10$F8fuHi9pTIZ6YFnVbg.hLu/bC3ZCDuNPCp2ZFnhUmf.7ROPf/tlzW'),('F2411300804','Anam','5884515454','Information Technology','1','$2y$10$sDgD3c77N9HXUCeG3FppzeMdy0iUwE9JwIa6pbj9wd9yT6RG7Ljyu'),('ST@01','Suman Kumar','4587585457','Computer Science','4th Semester','$2y$10$FrQryopnjrvHZQg.1f1Cm.yfc3CK.bhcF4eVWiM3XLo49h08h2fGS'),('ST@02','Sita','4785968568','Computer Science','5th Semester','$2y$10$VSdpTuRiJ2Uiy34vJ/TnAeZNCSueGvrwpMd.cplyjOn5YwoToDpHi'),('ST@03','Sekhar Pradhan','7588445555','Computer Science','5th Semester','$2y$10$gxN333cnrzr5tcWDsjJ6n.3cZy4V7Ojod69YTI82FQD/blx2IdUMe'),('ST@2001','Mamata Kumari','3598754845','Computer Science','5th Semester','$2y$10$00A9z1.kxVrA6NXvQoMSee3rytJnn/k1WQfsHFH1OewA.2Om3J/La'),('ST@2024','Dharama','5879857857','Computer Science','5th Semester','$2y$10$oFgVm6ikYkJ2AsULpEA8POgpQjsqYnH89Kt.acccJI1hVx74vH1xe');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-29  0:02:14
