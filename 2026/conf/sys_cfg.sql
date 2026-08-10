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
('conf/header.php', 139, 7287, '26805192919ef4b9948abb46d5e221c29bb3449a93bd25da25f4e3c886b208d3'),
('conf/footer.php', 122, 6770, 'e2fcada3a8187e066daf114ae0d42bd1676e5f348a319b1f2ca6068a51df180c'),
('index.php', 1482, 69641, '34efa135ff437765c3f96448f687c536e7266315b8e0bb3ef5bd86256a38c29d'),
('equipos.php', 2447, 106745, '5b09716096c2b739a4d76c2356e8c6352adafd8e157cb2884ef6fc420b8f0524'),
('robo_banco.php', 879, 48755, 'c1415daa70a610c51846cff3b6dddf1e2e12f6f968af5867e8e6af505904cb77'),
('conf/functions.php', 1225, 39821, '0a9336dcac3bd1af0d60b85d8ec73a23a25fd63b5523666da4629fa0c73151e9');
