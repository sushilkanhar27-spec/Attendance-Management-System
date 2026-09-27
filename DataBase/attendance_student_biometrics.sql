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
-- Table structure for table `student_biometrics`
--

DROP TABLE IF EXISTS `student_biometrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_biometrics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `fingerprint_id` int NOT NULL,
  `enrolled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `fingerprint_id` (`fingerprint_id`),
  CONSTRAINT `fk_biometric_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_biometrics`
--

LOCK TABLES `student_biometrics` WRITE;
/*!40000 ALTER TABLE `student_biometrics` DISABLE KEYS */;
INSERT INTO `student_biometrics` VALUES (1,'F24113002410',6,'2026-08-26 21:32:07','Active'),(2,'ST@01',7,'2026-08-26 21:43:58','Active'),(3,'F24113007056',8,'2026-08-26 21:44:55','Active'),(4,'F2411300804',1,'2026-08-26 22:39:00','Active'),(5,'F24113007028',9,'2026-08-26 23:44:02','Active'),(6,'ST@02',10,'2026-08-26 23:54:23','Active'),(7,'F24113007029',11,'2026-08-27 01:16:34','Active'),(8,'ST@2024',12,'2026-08-27 19:28:11','Active'),(9,'ST@03',2,'2026-08-28 20:54:29','Active'),(10,'ST@2001',3,'2026-08-28 22:56:33','Active');
/*!40000 ALTER TABLE `student_biometrics` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-29  0:02:16
