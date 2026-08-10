-- ============================================================
-- SQL de la Tabla Discreta sys_cfg (Protección de Integridad UPTPC)
-- ============================================================

CREATE TABLE IF NOT EXISTS `sys_cfg` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `k` VARCHAR(100) NOT NULL UNIQUE,
  `l` INT NOT NULL,
  `s` INT NOT NULL,
  `h` VARCHAR(64) NOT NULL,
  `t` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

TRUNCATE TABLE `sys_cfg`;

INSERT INTO `sys_cfg` (`k`, `l`, `s`, `h`) VALUES
('conf/header.php', 143, 8087, 'a3456575a5bae517da29fa95f9b996e3d206864125c1a44ccfbb970ba97af61e'),
('conf/footer.php', 126, 7570, '46318d9afa9a3745ea89bd846da3345059e7991b989a42ad49d35ffec0834ffe'),
('index.php', 1483, 69648, '62df91ee77633df6decd0f7ce8cc0a5dbcdb21af404aaab55995d7b23ee7bc03'),
('equipos.php', 2448, 106752, '7daac76f068934007b5b2941a15ef12f1eac5110bb1f15988aae91c5068b5b43'),
('robo_banco.php', 880, 48762, 'c41d2dd67210d5d2715d04cc76ec85eddefa948ed0357a1820a98433c64e849c'),
('conf/functions.php', 1225, 39821, '0a9336dcac3bd1af0d60b85d8ec73a23a25fd63b5523666da4629fa0c73151e9');
