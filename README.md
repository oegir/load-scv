# load-scv
Загрузка csv-файлов в MySQL базу данных

# Формат csv-файлов
Articul - строка,
Count - целое,
Price - целое

# Таблица в БД
CREATE TABLE `test` (
  `ID` int(18) NOT NULL AUTO_INCREMENT,
  `ARTICUL` varchar(50),
  `PRICE` int(18),
  `COUNT` int(18),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
