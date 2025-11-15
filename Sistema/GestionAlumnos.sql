-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para escuela_db
CREATE DATABASE IF NOT EXISTS `escuela_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `escuela_db`;

-- Volcando estructura para tabla escuela_db.alumno
CREATE TABLE IF NOT EXISTS `alumno` (
  `ID_Alumno` int NOT NULL AUTO_INCREMENT,
  `DNI` varchar(20) DEFAULT NULL,
  `Nombre` varchar(50) DEFAULT NULL,
  `Apellido` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_Alumno`),
  UNIQUE KEY `DNI` (`DNI`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla escuela_db.alumno_asistencia
CREATE TABLE IF NOT EXISTS `alumno_asistencia` (
  `ID_Asistencia` int NOT NULL AUTO_INCREMENT,
  `ID_Alumno_Clase` int DEFAULT NULL,
  `Periodo` enum('1C','2C','Anual') NOT NULL,
  `Total_Clases` int NOT NULL,
  `Asistencias` int NOT NULL,
  `Inasistencias` int NOT NULL,
  PRIMARY KEY (`ID_Asistencia`),
  KEY `ID_Alumno_Clase` (`ID_Alumno_Clase`),
  CONSTRAINT `alumno_asistencia_ibfk_1` FOREIGN KEY (`ID_Alumno_Clase`) REFERENCES `alumno_clase` (`ID_Alumno_Clase`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla escuela_db.alumno_clase
CREATE TABLE IF NOT EXISTS `alumno_clase` (
  `ID_Alumno_Clase` int NOT NULL AUTO_INCREMENT,
  `ID_Alumno` int DEFAULT NULL,
  `ID_Clase` int DEFAULT NULL,
  `Estado` enum('Regular','Libre','Promocionado','Baja') DEFAULT 'Regular',
  PRIMARY KEY (`ID_Alumno_Clase`),
  KEY `ID_Alumno` (`ID_Alumno`),
  KEY `ID_Clase` (`ID_Clase`),
  CONSTRAINT `alumno_clase_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`),
  CONSTRAINT `alumno_clase_ibfk_2` FOREIGN KEY (`ID_Clase`) REFERENCES `clase` (`ID_Clase`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla escuela_db.asistencia
CREATE TABLE IF NOT EXISTS `asistencia` (
  `ID_Asistencia` int NOT NULL AUTO_INCREMENT,
  `ID_Alumno_Clase` int DEFAULT NULL,
  `Fecha` date DEFAULT NULL,
  `Estado` enum('Presente','Ausente','Justificado') DEFAULT 'Presente',
  PRIMARY KEY (`ID_Asistencia`),
  KEY `ID_Alumno_Clase` (`ID_Alumno_Clase`),
  CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`ID_Alumno_Clase`) REFERENCES `alumno_clase` (`ID_Alumno_Clase`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla escuela_db.clase
CREATE TABLE IF NOT EXISTS `clase` (
  `ID_Clase` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) DEFAULT NULL,
  `ID_Profesor` int DEFAULT NULL,
  `ID_Instituto` int DEFAULT NULL,
  PRIMARY KEY (`ID_Clase`),
  KEY `ID_Profesor` (`ID_Profesor`),
  KEY `ID_Instituto` (`ID_Instituto`),
  CONSTRAINT `clase_ibfk_1` FOREIGN KEY (`ID_Profesor`) REFERENCES `profesor` (`ID_Profesor`),
  CONSTRAINT `clase_ibfk_2` FOREIGN KEY (`ID_Instituto`) REFERENCES `instituto` (`ID_Instituto`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla escuela_db.instituto
CREATE TABLE IF NOT EXISTS `instituto` (
  `ID_Instituto` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`ID_Instituto`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla escuela_db.nota
CREATE TABLE IF NOT EXISTS `nota` (
  `ID_Nota` int NOT NULL AUTO_INCREMENT,
  `ID_Alumno_Clase` int DEFAULT NULL,
  `Tipo` enum('1C','2C','Final','Recuperatorio') DEFAULT '1C',
  `Valor` decimal(4,2) DEFAULT NULL,
  `Fecha` date DEFAULT NULL,
  PRIMARY KEY (`ID_Nota`),
  KEY `ID_Alumno_Clase` (`ID_Alumno_Clase`),
  CONSTRAINT `nota_ibfk_1` FOREIGN KEY (`ID_Alumno_Clase`) REFERENCES `alumno_clase` (`ID_Alumno_Clase`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla escuela_db.profesor
CREATE TABLE IF NOT EXISTS `profesor` (
  `ID_Profesor` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) DEFAULT NULL,
  `Apellido` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_Profesor`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla escuela_db.usuario
CREATE TABLE IF NOT EXISTS `usuario` (
  `ID_Usuario` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Rol` enum('Alumno','Profesor','Admin') NOT NULL,
  `ID_Alumno` int DEFAULT NULL,
  `ID_Profesor` int DEFAULT NULL,
  PRIMARY KEY (`ID_Usuario`),
  UNIQUE KEY `Username` (`Username`),
  KEY `ID_Alumno` (`ID_Alumno`),
  KEY `ID_Profesor` (`ID_Profesor`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`),
  CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`ID_Profesor`) REFERENCES `profesor` (`ID_Profesor`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
