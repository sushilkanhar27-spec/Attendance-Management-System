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
-- Table structure for table `attendance_record`
--

DROP TABLE IF EXISTS `attendance_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_record` (
  `attendance_id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `teacher_id` varchar(20) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `branch_name` varchar(100) DEFAULT NULL,
  `attendance_method` enum('Teacher','Admin','Biometric') DEFAULT 'Biometric',
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `student_id` (`student_id`,`attendance_date`),
  UNIQUE KEY `unique_attendance` (`student_id`,`attendance_date`),
  UNIQUE KEY `uq_student_attendance_date` (`student_id`,`attendance_date`),
  KEY `fk_teacher` (`teacher_id`),
  CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_record`
--

LOCK TABLES `attendance_record` WRITE;
/*!40000 ALTER TABLE `attendance_record` DISABLE KEYS */;
INSERT INTO `attendance_record` VALUES (13,'F24113002410',NULL,'2026-07-01','Present','2026-07-25 18:26:01',NULL,'Biometric'),(14,'F24113002410',NULL,'2026-07-12','Present','2026-07-25 18:26:20',NULL,'Biometric'),(15,'F24113002410',NULL,'2026-07-09','Present','2026-07-25 18:38:18',NULL,'Biometric'),(16,'F24113002410',NULL,'2026-07-23','Present','2026-07-25 18:38:32',NULL,'Biometric'),(17,'F24113002410',NULL,'2026-07-20','Present','2026-07-25 18:38:47',NULL,'Biometric'),(18,'F24113002410',NULL,'2026-07-02','Present','2026-07-25 18:39:04',NULL,'Biometric'),(19,'F24113002410',NULL,'2026-07-03','Present','2026-07-25 18:39:23',NULL,'Biometric'),(20,'F2411300804',NULL,'2026-07-03','Present','2026-07-25 18:44:06',NULL,'Biometric'),(21,'F2411300804',NULL,'2026-07-04','Present','2026-07-25 18:44:19',NULL,'Biometric'),(24,'F2411300804',NULL,'2026-07-17','Present','2026-07-26 10:49:07',NULL,'Biometric'),(25,'F24113002410',NULL,'2026-07-08','Absent','2026-07-26 17:13:12',NULL,'Biometric'),(26,'F24113002410','T202406','2026-07-14','Absent','2026-07-26 17:25:23',NULL,'Biometric'),(27,'F24113002410',NULL,'2026-07-13','Absent','2026-07-26 17:26:05',NULL,'Biometric'),(28,'F24113007056','T202405','2026-07-23','Present','2026-07-26 19:42:01',NULL,'Biometric'),(29,'F24113007029','T202405','2026-07-23','Present','2026-07-26 19:42:01',NULL,'Biometric'),(32,'F24113007056','T202405','2026-07-22','Present','2026-07-26 20:03:40',NULL,'Biometric'),(33,'F24113007029','T202405','2026-07-22','Present','2026-07-26 20:03:46',NULL,'Biometric'),(34,'F24113007056','T202405','2026-07-07','Present','2026-07-26 20:05:30',NULL,'Biometric'),(35,'F24113007056','T202405','2026-07-09','Present','2026-07-26 20:11:22',NULL,'Biometric'),(36,'F24113007029','T202405','2026-07-09','Present','2026-07-26 20:12:52',NULL,'Biometric'),(37,'F24113007056','T202405','2026-07-06','Present','2026-07-26 20:15:22',NULL,'Biometric'),(38,'F24113007056','T202405','2026-07-17','Absent','2026-07-26 20:16:21',NULL,'Biometric'),(39,'F24113007056','T202405','2026-07-03','Present','2026-07-26 20:17:23',NULL,'Biometric'),(40,'F24113007056','T202405','2026-07-15','Present','2026-07-26 20:18:45',NULL,'Biometric'),(41,'F24113007056','T202405','2026-07-01','Absent','2026-07-27 08:30:24',NULL,'Biometric'),(42,'F24113007056','T202405','2026-08-13','Present','2026-08-16 18:17:26',NULL,'Biometric'),(43,'ST@01','T202405','2026-08-13','Present','2026-08-16 18:17:29',NULL,'Biometric'),(44,'F24113007029','T202405','2026-08-13','Absent','2026-08-16 18:19:29',NULL,'Biometric'),(51,'F24113007056','T202405','2026-08-11','Absent','2026-08-16 18:30:19',NULL,'Biometric'),(52,'ST@01','T202405','2026-08-11','Absent','2026-08-16 18:30:19',NULL,'Biometric'),(53,'F24113007029','T202405','2026-08-11','Absent','2026-08-16 18:30:19',NULL,'Biometric'),(54,'F24113007056','T202405','2026-08-10','Absent','2026-08-16 18:35:52',NULL,'Biometric'),(55,'ST@01','T202405','2026-08-10','Present','2026-08-16 18:35:52',NULL,'Biometric'),(56,'F24113007029','T202405','2026-08-10','Absent','2026-08-16 18:35:52',NULL,'Biometric'),(57,'F24113007056','T202405','2026-08-26','Absent','2026-08-26 18:58:22',NULL,'Biometric'),(58,'F24113007028','T202405','2026-08-26','Absent','2026-08-26 18:58:22',NULL,'Biometric'),(59,'ST@02','T202405','2026-08-26','Absent','2026-08-26 18:58:22',NULL,'Biometric'),(60,'ST@01','T202405','2026-08-26','Absent','2026-08-26 18:58:22',NULL,'Biometric'),(61,'F24113007029','T202405','2026-08-26','Absent','2026-08-26 18:58:22',NULL,'Biometric'),(102,'ST@03','T202405','2026-08-28','Present','2026-08-28 15:56:33','Computer Science','Biometric'),(103,'F24113007028','T202405','2026-08-28','Absent','2026-08-28 17:10:59','Computer Science','Biometric'),(104,'F24113007029','T202405','2026-08-28','Absent','2026-08-28 17:10:59','Computer Science','Biometric'),(105,'F24113007056','T202405','2026-08-28','Absent','2026-08-28 17:10:59','Computer Science','Biometric'),(106,'ST@01','T202405','2026-08-28','Absent','2026-08-28 17:10:59','Computer Science','Biometric'),(107,'ST@02','T202405','2026-08-28','Absent','2026-08-28 17:10:59','Computer Science','Biometric'),(108,'ST@2024','T202405','2026-08-28','Absent','2026-08-28 17:10:59','Computer Science','Biometric'),(109,'ST@2001','T202405','2026-08-28','Absent','2026-08-28 17:29:14','Computer Science','Biometric');
/*!40000 ALTER TABLE `attendance_record` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-29  0:02:17
