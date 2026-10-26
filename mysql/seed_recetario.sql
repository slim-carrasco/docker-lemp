-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: recetario
-- ------------------------------------------------------
-- Server version	8.0.46

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
-- Dumping data for table `carpetas`
--
-- WHERE:  local_id=1

LOCK TABLES `carpetas` WRITE;
/*!40000 ALTER TABLE `carpetas` DISABLE KEYS */;
INSERT INTO `carpetas` (`id`, `local_id`, `nombre`, `descripcion`, `color`, `fecha`) VALUES (1,1,'Comidas',NULL,'#4a9eff','2026-10-26 01:42:46');
/*!40000 ALTER TABLE `carpetas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `precios`
--
-- WHERE:  local_id=1

LOCK TABLES `precios` WRITE;
/*!40000 ALTER TABLE `precios` DISABLE KEYS */;
INSERT INTO `precios` (`id`, `local_id`, `nombre`, `precio`, `unidad`, `categoria`, `notas`, `actualizado`) VALUES (1,1,'Leche evaporada',4.00,'lata (400g)','Lácteos','Una lata rinde para 1 carlota','2026-08-20 00:58:40'),(2,1,'Leche condensada',4.00,'lata (397g)','Lácteos','Aprox S/ 1 por 100g','2026-08-20 00:58:40'),(3,1,'Queso crema comercial',12.00,'200g','Lácteos','Queso crema de marca comercial','2026-08-20 00:58:40'),(4,1,'Queso crema casero',4.00,'200g','Lácteos','Hecho en casa - vale la pena','2026-08-20 00:58:40'),(5,1,'Leche fresca de vaca',3.00,'1L','Lácteos','Directa del productor','2026-08-20 00:58:40'),(6,1,'Crema de leche',21.00,'1L','Lácteos','No vale la pena hacer en casa','2026-08-20 00:58:40'),(7,1,'Yogur griego casero',3.00,'400g','Lácteos','De 1L leche fresca - requiere termómetro','2026-08-20 00:58:40'),(8,1,'Mantequilla sin sal',10.50,'180g','Lácteos',NULL,'2026-08-20 00:58:40'),(9,1,'Mantequilla con sal',19.50,'390g','Lácteos',NULL,'2026-08-20 00:58:40'),(10,1,'Galletas María (tienda)',1.00,'60g','Secos','Opción más económica','2026-08-20 00:58:40'),(11,1,'Galletas María (supermercado)',3.50,'110g','Secos','Precio referencial','2026-08-20 00:58:40'),(12,1,'Azúcar blanca',0.20,'100g','Secos','Precio aproximado','2026-08-20 00:58:40'),(13,1,'Gelatina sin sabor',1.50,'sobre (10g)','Secos',NULL,'2026-08-20 00:58:40'),(14,1,'Harina de trigo',0.30,'100g','Secos',NULL,'2026-08-20 00:58:40'),(15,1,'Cocoa en polvo',0.50,'100g','Secos',NULL,'2026-08-20 00:58:40'),(16,1,'Esencia de vainilla',0.30,'5ml','Secos',NULL,'2026-08-20 00:58:40'),(17,1,'Limones',1.00,'10 unidades','Frutas','Aprox 10 unidades por sol','2026-08-20 00:58:40'),(18,1,'Maracuyá',0.50,'unidad','Frutas','Usar maduros con cáscara arrugada','2026-08-20 00:58:40'),(19,1,'Papaya',4.00,'1kg','Frutas','De 1kg se obtienen ~650g de pulpa limpia','2026-08-20 00:58:40'),(20,1,'Duraznos en almíbar',8.00,'lata (480g)','Frutas','También hay de S/ 10','2026-08-20 00:58:40'),(21,1,'Huevos',0.50,'unidad','Otros','Precio aproximado','2026-08-20 00:58:40'),(22,1,'Sal',0.05,'100g','Otros',NULL,'2026-08-20 00:58:40');
/*!40000 ALTER TABLE `precios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `recetas`
--
-- WHERE:  local_id=1

LOCK TABLES `recetas` WRITE;
/*!40000 ALTER TABLE `recetas` DISABLE KEYS */;
INSERT INTO `recetas` (`id`, `carpeta_id`, `nombre`, `descripcion`, `porciones_base`, `altitud`, `notas`, `fecha`, `local_id`, `creado_por`, `precio_venta`) VALUES (52,1,'Carlota de durazno','Postre frío sin horno. El limón espesa la crema de forma natural.',6,1,'Prueba el dulzor antes de echar el limón — una vez que lo agregas ya no puedes corregir bien.\nLeche evaporada fría del refri antes de licuar.\nPuno: saca el queso crema 30–40 min antes.','2026-08-18 23:30:49',1,NULL,NULL),(53,1,'Carlota de limón','Sin horno. El limón es el sabor principal — agrégalo poco a poco y controla tú la intensidad.',6,1,'Ralla siempre antes de exprimir.\nEl limón espesa más agresivo que en la de durazno — agrega poco a poco.\nLa ralladura va encima, nunca licuada adentro.\nPuno: saca el queso crema 30–40 min antes.\nSe puede servir con mermelada de maracuyá encima.','2026-08-18 23:30:49',1,NULL,NULL),(54,1,'Carlota de maracuyá','Sin horno, sin limón. El maracuyá es el ácido — agrégalo poco a poco y controla tú la intensidad.',6,1,'Sin limón — el maracuyá ya es ácido, agregar limón la corta.\nUsa maracuyás maduros con cáscara arrugada — dan mucho más jugo.\nPuno: saca el queso crema 30–40 min antes.','2026-08-18 23:30:49',1,NULL,NULL),(55,1,'Mermelada de maracuyá','Mermelada completa. Sirve para topping de carlota, yogur, pan, rellenos. Rinde ~1 frasco mediano.',1,1,'Para carlota: úsala fría del refri, nunca tibia.\nPuno: confía en el test del platito, no en el tiempo.\nSi quedó espesa: cucharada de agua tibia fuera del fuego.\nSi quedó líquida: 3 min más y repite.','2026-08-18 23:30:49',1,NULL,NULL),(56,1,'Mousse de maracuyá','Aireado y cremoso. Se sirve en copas individuales con galleta molida en el fondo y mermelada de maracuyá encima.',6,1,'La gelatina nunca caliente directo a la crema — se forman hilos.\nNo batas la crema de más — se convierte en mantequilla.\nMovimientos envolventes al incorporar — no pierdas el aire.\nPuno: la crema monta rápido con el frío, vigila.','2026-08-18 23:30:49',1,NULL,NULL),(57,1,'Carlota de papaya','La más barata de todas. El limón equilibra el dulzor natural de la papaya.',6,1,'La papaya varía mucho de dulzura — prueba siempre antes de echar el limón.\nMenos condensada que otras carlotas porque la papaya ya es muy dulce.\nPuno: saca el queso crema 30–40 min antes del refri.','2026-08-18 23:34:09',1,NULL,NULL),(58,1,'Mermelada de papaya','La papaya suelta su propio jugo al reposar con el azúcar — no necesita agua. Sirve para topping de carlota, yogur, pan.',1,1,'No necesita agua — la papaya suelta su propio jugo con el azúcar.\nSi quieres textura lisa: aplasta con tenedor o batidora al final.\nSi quieres con trocitos: déjala tal cual.\nPuno: confía en el test del platito, no en el tiempo.','2026-08-18 23:34:09',1,NULL,NULL),(59,1,'Cheesecake frío','Sin horno. La gelatina da la firmeza — nunca echarla caliente directo al queso.',8,1,'Necesitas molde desmontable 22–24cm para desmoldar limpio. Sin él usa el molde 20x20 forrado con plástico.\nLa gelatina siempre tibia, nunca caliente directo al queso.\nEl queso crema a temperatura ambiente — si está frío queda con grumos.\nPuno: la crema monta muy rápido, no te distraigas.','2026-08-18 23:34:09',1,NULL,NULL),(60,1,'Queso crema casero','Sale S/ 4 los 200g vs S/ 12 comercial. Vale la pena hacerlo.',1,0,'Rinde ~200g de queso crema.\nMás tiempo de colado = más firme. Para carlota 1h está bien. Para untar en pan 2–3h.\nEl suero que queda se puede usar en masas de pan o tomarlo solo.\nPuno: el frío ambiente ayuda al colado.','2026-08-18 23:38:19',1,NULL,NULL),(61,1,'Yogur griego casero','Sale S/ 1.50 los 200g vs S/ 8 comercial. Requiere termómetro para controlar temperatura de fermentación.',1,0,'REQUIERE TERMÓMETRO — sin él es difícil controlar la temperatura exacta.\nDe 1L de leche a S/ 3 obtienes ~400–450g de yogur griego.\nEl yogur comercial que uses como arranque debe tener cultivos vivos activos — revisa la etiqueta.\nEl suero que cae se puede tomar o usar en masas.\nGuarda 60g del yogur casero para el próximo arranque — no necesitas comprar yogur comercial cada vez.\nPuno: la frazada es crítica para mantener el calor durante la fermentación.','2026-08-18 23:38:19',1,NULL,NULL),(62,1,'Chuleta de Res a la Plancha con Salsa de Reducción','Chuletas de res selladas en sartén, con salsa de reducción de vino blanco montada con mantequilla. Acompañada de papas sancochadas y arroz graneado.',4,1,'Sustitución: vino blanco en vez de tinto (salsa más clara y ácida, no defecto). Vinagre tinto en vez de balsámico. Coordinar tiempos de guarniciones ANTES de montar la mantequilla — es más fácil pausar la reducción que recalentar salsa ya montada (se corta).','2026-08-22 02:55:39',1,NULL,NULL),(63,1,'Arroz con Leche','Arroz con leche cremoso con panela, canela en rama y pasas.',6,1,'Versión doble (validada): arroz 400g, leche 1200ml, agua 1200ml, crema 250g, panela 453g, azúcar extra 120g, pasas 100g. OJO rendimiento: con olla destapada la mitad rindió 1200ml reales (no 2.3L teóricos) por evaporación — el doble reales daría ~2.4L, no 4.6L, salvo que tapes la olla.','2026-08-22 02:59:19',1,NULL,NULL),(64,1,'Postre de Limón con Maicena y Galleta','Postre congelado tipo paleta/vasito, base de maicena y limón, con capa de galleta María semi-hundida.',4,1,'Alternativa excluyente probada: gelatina de limón con sabor (12g) en vez de la sin sabor — si usas esa, no uses la sin sabor. Refresco de limón (4g) es la opción original, no la usaste, la reemplazaste por zumo real (60-90ml / 3 limones), ya validado.','2026-08-22 02:59:28',1,NULL,NULL),(65,1,'Limonada con Hierbabuena (base con hielo)','Limonada clásica con hielo y hierbabuena machacada.',4,1,'Variantes probadas (misma proporción base de jugo/azúcar, cambia solo hierbabuena y proceso):\n- Sin hielo, machacada: 8-10 hojas/litro, agua bien fría de refri, infusionar 5-10 min.\n- Sin hielo, licuada: 6-7 hojas/litro, licuar 5-8 seg máximo junto con el jugo.\nDato clave: la hierbabuena NO espesa. El cuerpo viene de reducir el agua (más concentrado) o agregar pulpa/leche condensada (cambia el perfil a cremosa).\nVersión concentrada (750ml, sin hielo, licuada): jugo colado 150ml, agua fría 600ml, azúcar 90g, hierbabuena 6-7 hojas, licuar 5-8 seg.\nEscalado a 1.5L (doble del concentrado): jugo 300ml, agua 1200ml, azúcar 180g, hierbabuena 12-14 hojas. Ojo: licuadora doméstica probablemente necesita 2 tandas (capacidad típica 1-1.2L).','2026-08-22 02:59:41',1,NULL,NULL),(66,1,'Pollo Guisado con Arroz Blanco','Pollo sellado y guisado en sofrito de cebolla, ajo, tomate y ají amarillo, con arroz cocido en el caldo del guiso.',4,1,'Sabor tiró a estofado por falta de ají panca — considerar agregarlo la próxima vez. CANTIDADES DE ARROZ/AGUA CONVERTIDAS de tazas (2 tazas arroz, 3 tazas agua) usando 1 taza=200g / 1 taza=240ml — verificar si tu taza real coincide con esta conversión.','2026-08-22 02:59:49',1,NULL,NULL),(67,1,'Ropa Vieja con Arroz y Papas','Falda de res hervida en olla a presión, deshebrada y guisada en sofrito, con arroz cocido en el caldo de la carne y papas sancochadas aparte.',3,1,'CANTIDAD DE ARROZ CONVERTIDA de 1.5 tazas usando 1 taza=200g — verificar si tu taza real coincide.','2026-08-22 02:59:54',1,NULL,NULL),(68,1,'Espesante Base (sistema leche)','Base de maicena para helados/paletas del sistema de leche. Componente reutilizable, no un postre en sí mismo.',1,1,'🔄 EN AJUSTE. Rinde ~100-120g, variable — tiempo de cocción aún sin cronometrar de forma consistente (~5-9 min según fuego). Usar 100g de este espesante como base para cada receta del sistema de leche.','2026-08-24 02:34:16',1,NULL,NULL),(69,1,'Paleta Piña (sistema leche)','Paleta de piña con base de espesante (sistema leche).',4,1,'✅ Cerrada, sin pendientes.','2026-08-24 02:34:49',1,NULL,NULL),(70,1,'Paleta Fresa (sistema leche)','Paleta de fresa con base de espesante (sistema leche).',4,1,'✅ Cerrada, sin pendientes.','2026-08-24 02:35:00',1,NULL,NULL),(71,1,'Gaseosa Casera de Naranja','Bebida carbonatada por fermentación natural, jugo de naranja fresco. Rinde 1.5L total.',4,1,'Jugo siempre fresco, nunca guardado. Botella verificada antes de llenar. Levadura seca ≠ polvo para hornear. Usar báscula siempre, nunca cucharas. Nunca llenar hasta el tope. El pop fuerte es la señal de pasar al refrigerador.','2026-08-24 13:04:06',1,NULL,NULL),(72,1,'Chicharrón de Pota (tentáculo)','Pota cocida a presión, marinada en huevo especiado, rebozada en harina sazonada y frita. Servir con yuca o papa, sarsa criolla y limón.',4,1,'La cocción a presión (25-28 min) es lo que realmente ablanda la pota, no el limón — el limón sirve más para quitar olor/sabor a marisco que para ablandar; si lo dejas más de 2-3 min el ácido puede endurecer la proteína en vez de ayudarla. Salar recién en el marinado, nunca antes de la olla a presión.','2026-08-26 05:00:34',1,NULL,NULL);
/*!40000 ALTER TABLE `recetas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-27  0:46:49
-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: recetario
-- ------------------------------------------------------
-- Server version	8.0.46

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
-- Dumping data for table `ingredientes`
--
-- WHERE:  receta_id IN (SELECT id FROM recetas WHERE local_id=1)

LOCK TABLES `ingredientes` WRITE;
/*!40000 ALTER TABLE `ingredientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ingredientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `pasos`
--
-- WHERE:  receta_id IN (SELECT id FROM recetas WHERE local_id=1)

LOCK TABLES `pasos` WRITE;
/*!40000 ALTER TABLE `pasos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pasos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `errores`
--
-- WHERE:  receta_id IN (SELECT id FROM recetas WHERE local_id=1)

LOCK TABLES `errores` WRITE;
/*!40000 ALTER TABLE `errores` DISABLE KEYS */;
/*!40000 ALTER TABLE `errores` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-27  0:46:49
