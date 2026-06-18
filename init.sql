-- ============================================================
-- MOTUS — Script d'initialisation de la base de données
-- À exécuter dans phpMyAdmin ou via MySQL en ligne de commande
-- ============================================================

-- 1. Créer la base de données
CREATE DATABASE IF NOT EXISTS motus
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE motus;

-- 2. Créer la table "word" (champs exigés par le sujet + extras)
CREATE TABLE IF NOT EXISTS word (
    id        INT          NOT NULL AUTO_INCREMENT,
    word      VARCHAR(255) NOT NULL,
    score     INT          NOT NULL DEFAULT 0,
    played_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Lecture : SELECT * FROM word ORDER BY score DESC;
-- ============================================================
