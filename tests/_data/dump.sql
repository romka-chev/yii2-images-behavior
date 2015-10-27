CREATE TABLE "book" (
  `id`   INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` VARCHAR(255)
);
INSERT INTO `book` VALUES (1, 'Квантовая механика для бюрократов. Издание 25.3');
INSERT INTO `book` VALUES (2, 'Путешествия во времени для параноиков');
INSERT INTO `book` VALUES (3, 'ЗОЖ и блестящий зад - философия Бендера');
INSERT INTO `book` VALUES (4, 'Подбираем пенсне: автобиография Лилы');
INSERT INTO `book` VALUES (5, 'Правила съема - метод Фрая');

CREATE TABLE "image" (
  `id`        INTEGER PRIMARY KEY AUTOINCREMENT,
  `modelId`   INTEGER,
  `modelName` VARCHAR(255),
  `isMain`    TINYINT(1)          DEFAULT 0,
  `filePath`  VARCHAR(400)
)