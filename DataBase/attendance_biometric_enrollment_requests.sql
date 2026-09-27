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
-- Table structure for table `biometric_enrollment_requests`
--

DROP TABLE IF EXISTS `biometric_enrollment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `biometric_enrollment_requests` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `device_id` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Processing','Completed','Failed','Cancelled') DEFAULT 'Pending',
  `fingerprint_id` int DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biometric_enrollment_requests`
--

LOCK TABLES `biometric_enrollment_requests` WRITE;
/*!40000 ALTER TABLE `biometric_enrollment_requests` DISABLE KEYS */;
INSERT INTO `biometric_enrollment_requests` VALUES (23,'F24113002410','GATE_01','Failed',NULL,'Fingerprint registration failed','2026-08-26 21:31:28','2026-08-26 21:31:44'),(25,'F24113002410','GATE_01','Completed',6,'Biometric enrollment successful','2026-08-26 21:31:53','2026-08-26 21:32:07'),(26,'ST@01','GATE_01','Completed',7,'Biometric enrollment successful','2026-08-26 21:43:32','2026-08-26 21:43:58'),(27,'F24113007056','GATE_01','Completed',8,'Biometric enrollment successful','2026-08-26 21:44:36','2026-08-26 21:44:55'),(28,'F2411300804','GATE_01','Failed',NULL,'Fingerprint registration failed','2026-08-26 22:37:45','2026-08-26 22:37:55'),(29,'F2411300804','GATE_01','Failed',6,'Fingerprint ID already belongs to another student','2026-08-26 22:37:56','2026-08-26 22:38:17'),(30,'F2411300804','GATE_01','Completed',1,'Biometric enrollment successful','2026-08-26 22:38:18','2026-08-26 22:39:00'),(31,'F24113007028','GATE_01','Failed',NULL,'Fingerprint registration failed','2026-08-26 23:43:29','2026-08-26 23:43:42'),(32,'F24113007028','GATE_01','Completed',9,'Biometric enrollment successful','2026-08-26 23:43:43','2026-08-26 23:44:02'),(34,'ST@02','GATE_01','Failed',7,'Fingerprint ID already belongs to another student','2026-08-26 23:53:17','2026-08-26 23:53:41'),(35,'ST@02','GATE_01','Completed',10,'Biometric enrollment successful','2026-08-26 23:53:42','2026-08-26 23:54:23'),(36,'F24113007029','GATE_01','Completed',11,'Biometric enrollment successful','2026-08-27 01:16:05','2026-08-27 01:16:34'),(37,'ST@2024','GATE_01','Completed',12,'Biometric enrollment successful','2026-08-27 19:27:47','2026-08-27 19:28:11'),(38,'ST@03','GATE_01','Failed',1,'Fingerprint ID already belongs to another student','2026-08-28 20:53:46','2026-08-28 20:54:10'),(39,'ST@03','GATE_01','Completed',2,'Biometric enrollment successful','2026-08-28 20:54:10','2026-08-28 20:54:29'),(41,'ST@2001','GATE_01','Completed',3,'Biometric enrollment successful','2026-08-28 22:55:44','2026-08-28 22:56:33');
/*!40000 ALTER TABLE `biometric_enrollment_requests` ENABLE KEYS */;
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
