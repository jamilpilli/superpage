-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: superpagebd
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `theme_id` int DEFAULT NULL,
  `design` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_domain` (`domain`),
  KEY `idx_slug` (`slug`),
  KEY `theme_id` (`theme_id`),
  CONSTRAINT `sites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sites_ibfk_2` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sites_chk_1` CHECK (json_valid(`design`))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES (1,1,NULL,'lemonblue','active','2026-03-10 22:37:47','2026-03-12 10:46:27',1,'{\"primary_color\":\"#2589f4\",\"title_font\":\"Inter\",\"text_font\":\"Lato\",\"button_style\":\"rounded-full\"}'),(2,1,NULL,'minishop','inactive','2026-03-11 01:03:33','2026-03-11 01:13:31',1,'{\"primary_color\":\"#0d00ff\",\"title_font\":\"Inter\",\"text_font\":\"Inter\",\"button_style\":\"rounded\"}'),(3,1,NULL,'superpage','inactive','2026-03-11 01:12:17','2026-03-11 01:13:38',1,NULL),(4,1,NULL,'alpha-vet','inactive','2026-03-11 02:10:34','2026-03-11 15:24:25',1,NULL),(5,2,NULL,'algodao-doce','active','2026-03-11 10:59:38','2026-03-12 08:55:21',1,'{\"primary_color\":\"#c7ffdb\",\"title_font\":\"Playfair Display\",\"text_font\":\"Lato\",\"button_style\":\"rounded-full\"}'),(6,2,NULL,'the-cocoa-pod','active','2026-03-17 16:53:27','2026-03-17 17:10:45',1,'{\"primary_color\":\"#cf460f\",\"title_font\":\"Playfair Display\",\"text_font\":\"Roboto\",\"button_style\":\"rounded\"}');
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `site_id` int NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('published','draft') COLLATE utf8mb4_unicode_ci DEFAULT 'published',
  `seo_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_site_slug` (`site_id`,`slug`),
  CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pages_chk_1` CHECK (json_valid(`seo_data`))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,1,'home','Lemonblue','published',NULL,'2026-03-10 22:37:47','2026-03-10 22:37:47'),(2,2,'home','MiniShop','published',NULL,'2026-03-11 01:03:33','2026-03-11 01:03:33'),(3,3,'home','Superpage','published',NULL,'2026-03-11 01:12:17','2026-03-11 01:12:17'),(4,4,'home','Alpha Vet','published',NULL,'2026-03-11 02:10:34','2026-03-11 02:10:34'),(5,5,'home','Algod├úo Doce','published',NULL,'2026-03-11 10:59:38','2026-03-11 10:59:38'),(6,6,'home','The Cocoa Pod','published',NULL,'2026-03-17 16:53:27','2026-03-17 16:53:27');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `page_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_order` (`page_id`,`sort_order`),
  CONSTRAINT `blocks_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blocks_chk_1` CHECK (json_valid(`config`))
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocks`
--

LOCK TABLES `blocks` WRITE;
/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
INSERT INTO `blocks` VALUES (1,1,'header',0,'{\"title\":\"Lemonblue Mkt\",\"description\":\"\",\"button_text\":\"\",\"text\":\"\",\"image\":\"\\/uploads\\/users\\/1\\/00cc59868c.webp\",\"button_link\":\"\",\"items\":[],\"is_active\":true,\"gallery_images\":[],\"videos\":[]}','2026-03-10 22:37:47','2026-03-11 10:35:07'),(2,1,'hero',1,'{\"title\":\"\",\"description\":\"\",\"button_text\":\"\",\"text\":\"\",\"image\":\"\",\"button_link\":\"\",\"items\":[{\"title\":\"Cria\\u00e7\\u00e3o de Sites!\",\"description\":\"Somos especialistas em criar Portais de Not\\u00edcias e de Conte\\u00fado que se destacam!\",\"image\":\"\\/uploads\\/users\\/1\\/17db7d21fc.webp\",\"button_text\":\"Contato\",\"button_link\":\"#contato\"}],\"gallery_images\":[],\"videos\":[]}','2026-03-10 22:37:47','2026-03-11 10:35:41'),(3,1,'footer',9,'{\"title\":\"Lemonblue Mkt - Crie seu site!\",\"description\":\"\",\"button_text\":\"\",\"text\":\"\",\"image\":\"\",\"button_link\":\"\",\"items\":[]}','2026-03-10 22:37:47','2026-03-11 02:15:44'),(5,1,'about',2,'{\"title\":\"Sobre\",\"description\":\"\",\"button_text\":\"\",\"text\":\"A Lemon Blue atua no mercado de Marketing Digital h\\u00e1 mais de 19 anos.\\nContamos com uma equipe especializada em transformar ideias em solu\\u00e7\\u00f5es digitais eficientes, combinando os melhores conceitos, metodologias e tecnologias do mercado. Nosso foco \\u00e9 oferecer resultados reais e personalizados para cada cliente.\\n\\nSomos especialistas na cria\\u00e7\\u00e3o de sites e portais de not\\u00edcias, entregando projetos completos \\u2014 do planejamento \\u00e0 implementa\\u00e7\\u00e3o \\u2014 para que sua presen\\u00e7a online seja exatamente como voc\\u00ea imaginou (ou ainda melhor).\",\"image\":\"\\/uploads\\/users\\/1\\/2c1c2874ff.webp\",\"button_link\":\"\",\"items\":[],\"gallery_images\":[],\"videos\":[]}','2026-03-10 22:39:42','2026-03-11 10:36:39'),(6,1,'services',3,'{\"title\":\"Servi\\u00e7os\",\"description\":\"Conhe\\u00e7a todos os nossos servi\\u00e7os.\",\"button_text\":\"\",\"text\":\"\",\"image\":\"\",\"button_link\":\"\",\"items\":[{\"title\":\"Cria\\u00e7\\u00e3o de Sites\",\"description\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.\",\"image\":\"\",\"button_text\":\"Entrar em Contato\",\"button_link\":\"#contato\"},{\"title\":\"Email Marketing\",\"description\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.\",\"image\":\"\",\"button_text\":\"Entrar em Contato\",\"button_link\":\"#contato\"},{\"title\":\"Blog Profissional\",\"description\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.\",\"image\":\"\",\"button_text\":\"Entrar em Contato\",\"button_link\":\"#contato\"}]}','2026-03-10 22:40:06','2026-03-11 00:58:13'),(12,1,'products',4,'{\"title\":\"Produtos\",\"description\":\"\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[{\"title\":\"Tenis Azul\",\"description\":\"Testando o box de produtos.\",\"image\":\"\\/uploads\\/users\\/1\\/f785698d15.webp\",\"button_text\":\"Comprar\",\"button_link\":\"#\"},{\"title\":\"Tenis Preto\",\"description\":\"Testando o box de produtos.\",\"image\":\"\\/uploads\\/users\\/1\\/60e8865300.webp\",\"button_text\":\"Comprar\",\"button_link\":\"#\"},{\"title\":\"Tenis Verde\",\"description\":\"Testando o box de produtos.\",\"image\":\"\\/uploads\\/users\\/1\\/39381b26fc.webp\",\"button_text\":\"Comprar\",\"button_link\":\"#\"},{\"title\":\"Tenis Roxo\",\"description\":\"Testando o box de produtos.\",\"image\":\"\\/uploads\\/users\\/1\\/501102e59d.webp\",\"button_text\":\"Comprar\",\"button_link\":\"#\"}],\"gallery_images\":[],\"videos\":[]}','2026-03-11 00:46:43','2026-03-11 10:37:03'),(13,1,'testimonials',7,'{\"title\":\"Depoimentos\",\"description\":\"\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[{\"title\":\"Joao da Silva\",\"description\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\"},{\"title\":\"Fernando Prates\",\"description\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\"},{\"title\":\"Pedro Souza\",\"description\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\"}]}','2026-03-11 00:46:45','2026-03-11 02:15:44'),(14,2,'header',0,'{\"title\":\"MiniShop\"}','2026-03-11 01:03:33','2026-03-11 01:03:33'),(15,2,'hero',1,'{\"title\":\"Bem-vindo ao MiniShop\"}','2026-03-11 01:03:33','2026-03-11 01:03:33'),(16,2,'footer',7,'{\"title\":\"MiniShop\"}','2026-03-11 01:03:33','2026-03-11 01:05:19'),(17,2,'about',2,'{\"title\":\"Novo Bloco About\"}','2026-03-11 01:05:11','2026-03-11 01:05:19'),(18,2,'services',3,'{\"title\":\"Novo Bloco Services\"}','2026-03-11 01:05:12','2026-03-11 01:05:19'),(19,2,'products',4,'{\"title\":\"Novo Bloco Products\"}','2026-03-11 01:05:12','2026-03-11 01:05:19'),(20,2,'testimonials',5,'{\"title\":\"Novo Bloco Testimonials\"}','2026-03-11 01:05:13','2026-03-11 01:05:19'),(21,2,'contact',6,'{\"title\":\"Novo Bloco Contact\"}','2026-03-11 01:05:14','2026-03-11 01:05:19'),(22,3,'header',0,'{\"title\":\"Superpage\"}','2026-03-11 01:12:17','2026-03-11 01:12:17'),(23,3,'hero',1,'{\"title\":\"Bem-vindo ao Superpage\"}','2026-03-11 01:12:17','2026-03-11 01:12:17'),(24,3,'about',2,'{\"title\":\"Sobre N\\u00f3s\",\"description\":\"Um pouco sobre nossa hist\\u00f3ria e valores.\"}','2026-03-11 01:12:17','2026-03-11 01:12:17'),(25,3,'services',3,'{\"title\":\"Nossos Servi\\u00e7os\",\"description\":\"Conhe\\u00e7a o que podemos fazer por voc\\u00ea.\"}','2026-03-11 01:12:17','2026-03-11 01:12:17'),(26,3,'products',4,'{\"title\":\"Nossos Produtos\",\"description\":\"Explore as nossas melhores op\\u00e7\\u00f5es para voc\\u00ea.\"}','2026-03-11 01:12:17','2026-03-11 01:12:17'),(27,3,'testimonials',5,'{\"title\":\"Depoimentos\"}','2026-03-11 01:12:17','2026-03-11 01:12:17'),(28,3,'contact',6,'{\"title\":\"Entre em Contato\",\"button_text\":\"Enviar Mensagem\"}','2026-03-11 01:12:17','2026-03-11 01:12:17'),(29,3,'footer',7,'{\"title\":\"Superpage\"}','2026-03-11 01:12:17','2026-03-11 01:12:17'),(30,1,'contact',8,'{\"title\":\"Contato\",\"description\":\"\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[],\"phone\":\"11999915781\",\"is_whatsapp\":true,\"email\":\"lemonblue.internet@gmail.com\",\"gallery_images\":[],\"videos\":[]}','2026-03-11 01:21:33','2026-03-11 10:41:39'),(31,4,'header',0,'{\"title\":\"Alpha Vet\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(32,4,'hero',1,'{\"title\":\"Bem-vindo ao Alpha Vet\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(33,4,'about',2,'{\"title\":\"Sobre N\\u00f3s\",\"description\":\"Um pouco sobre nossa hist\\u00f3ria e valores.\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(34,4,'services',3,'{\"title\":\"Nossos Servi\\u00e7os\",\"description\":\"Conhe\\u00e7a o que podemos fazer por voc\\u00ea.\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(35,4,'products',4,'{\"title\":\"Nossos Produtos\",\"description\":\"Explore as nossas melhores op\\u00e7\\u00f5es para voc\\u00ea.\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(36,4,'gallery',5,'{\"title\":\"Galeria de Fotos\",\"description\":\"Confira nossos melhores momentos e imagens.\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(37,4,'videos',6,'{\"title\":\"V\\u00eddeos\",\"description\":\"Assista aos nossos v\\u00eddeos institucionais.\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(38,4,'testimonials',7,'{\"title\":\"Depoimentos\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(39,4,'contact',8,'{\"title\":\"Entre em Contato\",\"button_text\":\"Enviar Mensagem\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(40,4,'footer',9,'{\"title\":\"Alpha Vet\"}','2026-03-11 02:10:34','2026-03-11 02:10:34'),(41,1,'gallery',6,'{\"title\":\"Galeria de Fotos\",\"description\":\"\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[],\"gallery_images\":[\"\\/uploads\\/users\\/1\\/93c78bcf93.webp\",\"\\/uploads\\/users\\/1\\/6ac9d8c6a7.webp\",\"\\/uploads\\/users\\/1\\/c6e07beb66.webp\",\"\\/uploads\\/users\\/1\\/86db993459.webp\",\"\\/uploads\\/users\\/1\\/e23070a0db.webp\",\"\\/uploads\\/users\\/1\\/5f4af37f4e.webp\",\"\\/uploads\\/users\\/1\\/95e6ccbec1.webp\",\"\\/uploads\\/users\\/1\\/630ba59cbd.webp\"],\"videos\":[]}','2026-03-11 02:15:37','2026-03-11 10:37:25'),(42,1,'videos',5,'{\"title\":\"V\\u00eddeos\",\"description\":\"\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[],\"gallery_images\":[],\"videos\":[{\"title\":\"\",\"url\":\"https:\\/\\/www.youtube.com\\/watch?v=THPZVS0lJRs\",\"is_active\":true},{\"title\":\"\",\"url\":\"https:\\/\\/www.youtube.com\\/watch?v=3AgwbBJfb3o\",\"is_active\":true},{\"title\":\"\",\"url\":\"https:\\/\\/www.youtube.com\\/watch?v=81htJmDb_08\",\"is_active\":true}]}','2026-03-11 02:15:38','2026-03-11 02:27:00'),(43,5,'header',0,'{\"title\":\"Algod\\u00e3o Doce\"}','2026-03-11 10:59:38','2026-03-11 10:59:38'),(44,5,'hero',1,'{\"title\":\"Bem-vindo ao Algod\\u00e3o Doce\",\"description\":\"\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[{\"title\":\"Melhor Algod\\u00e3o Doce da Regi\\u00e3o \",\"description\":\"A do\\u00e7ura de ser crian\\u00e7a \",\"image\":\"\\/uploads\\/users\\/2\\/f50d4854d5.webp\",\"button_text\":\"\",\"button_link\":\"\"}],\"gallery_images\":[],\"videos\":[]}','2026-03-11 10:59:38','2026-03-11 11:04:45'),(45,5,'about',2,'{\"title\":\"Sobre N\\u00f3s\",\"description\":\"Um pouco sobre nossa hist\\u00f3ria e valores.\",\"text\":\"Leve do\\u00e7ura e cor para o seu evento! Somos especialistas em algod\\u00e3o doce para festas, oferecendo sabores criativos e uma experi\\u00eancia inesquec\\u00edvel. De casamentos a anivers\\u00e1rios, encante seus convidados\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[],\"gallery_images\":[],\"videos\":[]}','2026-03-11 10:59:38','2026-03-11 11:12:57'),(46,5,'services',3,'{\"title\":\"Nossos Servi\\u00e7os\",\"description\":\"Conhe\\u00e7a o que podemos fazer por voc\\u00ea.\"}','2026-03-11 10:59:38','2026-03-11 10:59:38'),(47,5,'products',4,'{\"title\":\"Nossos Produtos\",\"description\":\"Explore as nossas melhores op\\u00e7\\u00f5es para voc\\u00ea.\"}','2026-03-11 10:59:38','2026-03-11 10:59:38'),(48,5,'gallery',5,'{\"title\":\"Galeria de Fotos\",\"description\":\"Confira nossos melhores momentos e imagens.\"}','2026-03-11 10:59:38','2026-03-11 10:59:38'),(49,5,'videos',6,'{\"title\":\"V\\u00eddeos\",\"description\":\"Assista aos nossos v\\u00eddeos institucionais.\"}','2026-03-11 10:59:38','2026-03-11 10:59:38'),(50,5,'testimonials',7,'{\"title\":\"Depoimentos\"}','2026-03-11 10:59:38','2026-03-11 10:59:38'),(51,5,'contact',8,'{\"title\":\"Entre em Contato\",\"button_text\":\"Enviar Mensagem\"}','2026-03-11 10:59:38','2026-03-11 10:59:38'),(52,5,'footer',9,'{\"title\":\"Algod\\u00e3o Doce\"}','2026-03-11 10:59:38','2026-03-11 10:59:38'),(53,6,'header',0,'{\"title\":\"The Cocoa Pod\",\"description\":\"\",\"text\":\"\",\"image\":\"\\/uploads\\/users\\/2\\/1776fff8c1.webp\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[],\"gallery_images\":[],\"videos\":[]}','2026-03-17 16:53:27','2026-03-17 17:14:05'),(54,6,'hero',1,'{\"title\":\"Bem-vindo ao The Cocoa Pod\",\"description\":\"\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[{\"title\":\"The Cocoa Pod\",\"description\":\" is all about exploring the world via the lens of chocolate!\",\"image\":\"\\/uploads\\/users\\/2\\/e3445b5e13.webp\",\"button_text\":\"\",\"button_link\":\"\"}],\"gallery_images\":[],\"videos\":[]}','2026-03-17 16:53:27','2026-03-17 16:56:39'),(55,6,'about',3,'{\"title\":\"About Us\",\"description\":\"Um pouco sobre nossa hist\\u00f3ria e valores.\",\"text\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\\n\",\"image\":\"\\/uploads\\/users\\/2\\/ace8cd6136.webp\",\"button_text\":\"Send Mensage\",\"button_link\":\"https:\\/\\/www.google.com \",\"items\":[],\"gallery_images\":[],\"videos\":[]}','2026-03-17 16:53:27','2026-03-17 17:32:13'),(56,6,'services',4,'{\"title\":\"Services\",\"description\":\"The standard Lorem Ipsum passage, used since the 1500s\\n\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\\\"\\n\\nSection 1.10.32 of \\\"de Finibus Bonorum et Malorum\\\", written by Cicero in 45 BC\\n\\\"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?\\\"\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[{\"title\":\"Chocolates \",\"description\":\"The standard Lorem Ipsum passage, used since the 1500s\\n\\\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim ven\",\"image\":\"\\/uploads\\/users\\/2\\/d480a6259e.webp\",\"button_text\":\"\",\"button_link\":\"\"}],\"gallery_images\":[],\"videos\":[],\"is_active\":false}','2026-03-17 16:53:27','2026-03-17 17:06:24'),(57,6,'products',2,'{\"title\":\"Our Products\",\"description\":\"Explore as nossas melhores op\\u00e7\\u00f5es para voc\\u00ea.\",\"text\":\"\",\"image\":\"\",\"button_text\":\"\",\"button_link\":\"\",\"items\":[{\"title\":\"Chocolates A \",\"description\":\"\",\"image\":\"\\/uploads\\/users\\/2\\/5b448480db.webp\",\"button_text\":\"\",\"button_link\":\"\"}],\"gallery_images\":[],\"videos\":[]}','2026-03-17 16:53:27','2026-03-17 17:06:24'),(58,6,'gallery',5,'{\"title\":\"Galeria de Fotos\",\"description\":\"Confira nossos melhores momentos e imagens.\",\"is_active\":false}','2026-03-17 16:53:27','2026-03-17 17:06:28'),(59,6,'videos',6,'{\"title\":\"V\\u00eddeos\",\"description\":\"Assista aos nossos v\\u00eddeos institucionais.\",\"is_active\":false}','2026-03-17 16:53:27','2026-03-17 17:06:30'),(60,6,'testimonials',7,'{\"title\":\"Depoimentos\",\"is_active\":true}','2026-03-17 16:53:27','2026-03-17 17:31:39'),(61,6,'contact',8,'{\"title\":\"Contact\",\"button_text\":\"Send Message \",\"description\":\"\",\"text\":\"\",\"image\":\"\",\"button_link\":\"\",\"items\":[],\"gallery_images\":[],\"videos\":[],\"email\":\"thecocoapod88@gmail.com\",\"phone\":\"44 - 082748492743\",\"is_whatsapp\":true}','2026-03-17 16:53:27','2026-03-17 17:31:19'),(62,6,'footer',9,'{\"title\":\"The Cocoa Pod\"}','2026-03-17 16:53:27','2026-03-17 16:53:27');
/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-23 21:30:13
