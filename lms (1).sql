-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : sam. 24 mai 2025 à 13:18
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1
DROP DATABASE IF EXISTS lms;
CREATE DATABASE lms;
USE lms;


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `lms`
--

-- --------------------------------------------------------

--
-- Structure de la table `absences`
--

CREATE TABLE `absences` (
  `id` int(11) NOT NULL,
  `planning_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `statut` enum('present','absent','justifie') DEFAULT 'absent',
  `justificatif` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `absences`
--

INSERT INTO `absences` (`id`, `planning_id`, `etudiant_id`, `statut`, `justificatif`) VALUES
(1, 1, 2, 'present', 'present phisyquement');

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `nom`) VALUES
(1, 'A621'),
(2, 'A622'),
(3, 'B623');

-- --------------------------------------------------------

--
-- Structure de la table `cours`
--

CREATE TABLE `cours` (
  `id` int(11) NOT NULL,
  `prof_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `module_id` int(11) NOT NULL,
  `fichier_path` varchar(255) DEFAULT NULL,
  `lien_externe` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `cours`
--

INSERT INTO `cours` (`id`, `prof_id`, `nom`, `description`, `module_id`, `fichier_path`, `lien_externe`) VALUES
(1, 1, 'limit', NULL, 1, 'uploads/1747941610_CHECKLIST 2022-23 conv.pdf', NULL),
(2, 1, 'matrice', NULL, 1, 'uploads/1747945061_682f46dd7d27c_logo-ecole-tech.png', NULL),
(3, 1, 'matrice', NULL, 1, 'uploads/1747945102_682f46dd7d27c_logo-ecole-tech.png', NULL),
(4, 1, 'front end', NULL, 2, 'uploads/1747993760_682f46dd7d27c_logo-ecole-tech.png', NULL),
(5, 1, 'php', NULL, 2, 'uploads/1748032145_682f46dd7d27c_logo-ecole-tech.png', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `devoirs`
--

CREATE TABLE `devoirs` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text,
  `fichier_path` varchar(255) DEFAULT NULL,
  `date_publication` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_echeance` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `devoirs`
--

INSERT INTO `devoirs` (`id`, `titre`, `description`, `fichier_path`, `date_publication`, `date_echeance`) VALUES
(1, 'test', NULL, 'uploads/prof/682f469dc81a5_Cahier_des_Charges_LMS_Projet_Techno.docx', '2025-05-22 17:45:33', '2025-07-07');

-- --------------------------------------------------------

--
-- Structure de la table `devoirs_remises`
--

CREATE TABLE `devoirs_remises` (
  `id` int(11) NOT NULL,
  `devoir_id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  `fichier_path` varchar(255) NOT NULL,
  `date_remise` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `devoirs_remises`
--

INSERT INTO `devoirs_remises` (`id`, `devoir_id`, `eleve_id`, `fichier_path`, `date_remise`) VALUES
(1, 1, 1, 'uploads/eleves/682f46dd7d27c_logo-ecole-tech.png', '2025-05-22 17:46:37'),
(2, 1, 2, 'uploads/eleves/682f862ca6dd8_682f46dd7d27c_logo-ecole-tech.png', '2025-05-22 22:16:44');

-- --------------------------------------------------------

--
-- Structure de la table `fichiers`
--

CREATE TABLE `fichiers` (
  `id` int(11) NOT NULL,
  `nom_fichier` varchar(255) DEFAULT NULL,
  `chemin` varchar(255) DEFAULT NULL,
  `id_cours` int(11) NOT NULL,
  `date_upload` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `forum_reponses`
--

CREATE TABLE `forum_reponses` (
  `id` int(11) NOT NULL,
  `sujet_id` int(11) NOT NULL,
  `auteur_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_post` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `forum_reponses`
--

INSERT INTO `forum_reponses` (`id`, `sujet_id`, `auteur_id`, `contenu`, `date_post`) VALUES
(1, 1, 2, 'valide', '2025-05-24 02:08:09');

-- --------------------------------------------------------

--
-- Structure de la table `forum_sujets`
--

CREATE TABLE `forum_sujets` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `auteur_id` int(11) NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `forum_sujets`
--

INSERT INTO `forum_sujets` (`id`, `titre`, `contenu`, `auteur_id`, `date_creation`) VALUES
(1, 'test', 'test', 2, '2025-05-24 02:08:09');

-- --------------------------------------------------------

--
-- Structure de la table `messages_prives`
--

CREATE TABLE `messages_prives` (
  `id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `id_prof` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `modules`
--

INSERT INTO `modules` (`id`, `nom`, `description`, `id_prof`) VALUES
(1, 'math', NULL, 1),
(2, 'dev web', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `planning`
--

CREATE TABLE `planning` (
  `id` int(11) NOT NULL,
  `cours_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `planning`
--

INSERT INTO `planning` (`id`, `cours_id`, `classe_id`, `jour`, `heure_debut`, `heure_fin`) VALUES
(1, 1, 1, 'Lundi', '10:00:00', '11:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `texte_question` text NOT NULL,
  `type_question` enum('simple','multiple') DEFAULT 'simple'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `questions`
--

INSERT INTO `questions` (`id`, `test_id`, `texte_question`, `type_question`) VALUES
(1, 1, 'fonctionne ?', 'simple');

-- --------------------------------------------------------

--
-- Structure de la table `reponses_etudiants`
--

CREATE TABLE `reponses_etudiants` (
  `id` int(11) NOT NULL,
  `resultat_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `reponse_id` int(11) DEFAULT NULL,
  `est_correcte` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `reponses_etudiants`
--

INSERT INTO `reponses_etudiants` (`id`, `resultat_id`, `question_id`, `reponse_id`, `est_correcte`) VALUES
(1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `reponses_possibles`
--

CREATE TABLE `reponses_possibles` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `texte_reponse` text NOT NULL,
  `est_correcte` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `reponses_possibles`
--

INSERT INTO `reponses_possibles` (`id`, `question_id`, `texte_reponse`, `est_correcte`) VALUES
(1, 1, 'oui', 1),
(2, 1, 'non', 0),
(3, 1, 'pttr', 0),
(4, 1, 'mdr', 0);

-- --------------------------------------------------------

--
-- Structure de la table `resultats_qcm`
--

CREATE TABLE `resultats_qcm` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `pourcentage` decimal(5,2) NOT NULL,
  `note` varchar(50) NOT NULL,
  `temps_passe` int(11) DEFAULT NULL COMMENT 'en secondes',
  `date_soumission` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `resultats_qcm`
--

INSERT INTO `resultats_qcm` (`id`, `test_id`, `etudiant_id`, `score`, `total_questions`, `pourcentage`, `note`, `temps_passe`, `date_soumission`) VALUES
(1, 1, 2, 1, 1, '100.00', '20/20', 7, '2025-05-24 01:25:29');

-- --------------------------------------------------------

--
-- Structure de la table `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `systeme_notation` enum('10','20','100','mention') DEFAULT '20',
  `temps_limite` int(11) DEFAULT '0' COMMENT 'en minutes',
  `module_id` int(11) DEFAULT NULL,
  `createur_id` int(11) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `tests`
--

INSERT INTO `tests` (`id`, `titre`, `systeme_notation`, `temps_limite`, `module_id`, `createur_id`, `date_creation`) VALUES
(1, 'test2', '20', 30, 1, 1, '2025-05-24 00:50:13');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `mdp` varchar(255) NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `mail`, `mdp`, `type`) VALUES
(1, 'moi', 'moi', 'moi@gmail.com', '$2y$10$ZxT/bSziXKdWhKZ0A9nuS.tXL86LpUMe/vwlfvS5LRItCKhjf2LOm', 2),
(2, 'ben ben', 'oui', 'benben@gmail.com', '$2y$10$CjAFrxt3SeaeNqJbM/TAlOph9eKLrTUKckfddLHWgWyZ6yqRLarDW', 1),
(3, 'mac', 'mac', 'mac@gmail.com', '$2y$10$hHY5/hJTW8M0xB4KnKh02uDaLUFRR44FLCFNnZiZh.ezUlVwUtXda', 3);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `absences`
--
ALTER TABLE `absences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `planning_id` (`planning_id`),
  ADD KEY `etudiant_id` (`etudiant_id`);

--
-- Index pour la table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `cours`
--
ALTER TABLE `cours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Index pour la table `devoirs`
--
ALTER TABLE `devoirs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `devoirs_remises`
--
ALTER TABLE `devoirs_remises`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `fichiers`
--
ALTER TABLE `fichiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cours` (`id_cours`);

--
-- Index pour la table `forum_reponses`
--
ALTER TABLE `forum_reponses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sujet_id` (`sujet_id`),
  ADD KEY `auteur_id` (`auteur_id`);

--
-- Index pour la table `forum_sujets`
--
ALTER TABLE `forum_sujets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auteur_id` (`auteur_id`);

--
-- Index pour la table `messages_prives`
--
ALTER TABLE `messages_prives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expediteur_id` (`expediteur_id`),
  ADD KEY `destinataire_id` (`destinataire_id`);

--
-- Index pour la table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_prof` (`id_prof`);

--
-- Index pour la table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cours_id` (`cours_id`),
  ADD KEY `classe_id` (`classe_id`);

--
-- Index pour la table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`);

--
-- Index pour la table `reponses_etudiants`
--
ALTER TABLE `reponses_etudiants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resultat_id` (`resultat_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `reponse_id` (`reponse_id`);

--
-- Index pour la table `reponses_possibles`
--
ALTER TABLE `reponses_possibles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Index pour la table `resultats_qcm`
--
ALTER TABLE `resultats_qcm`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`),
  ADD KEY `etudiant_id` (`etudiant_id`);

--
-- Index pour la table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `createur_id` (`createur_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mail` (`mail`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `absences`
--
ALTER TABLE `absences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `cours`
--
ALTER TABLE `cours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `devoirs`
--
ALTER TABLE `devoirs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `devoirs_remises`
--
ALTER TABLE `devoirs_remises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `fichiers`
--
ALTER TABLE `fichiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `forum_reponses`
--
ALTER TABLE `forum_reponses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `forum_sujets`
--
ALTER TABLE `forum_sujets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `messages_prives`
--
ALTER TABLE `messages_prives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `planning`
--
ALTER TABLE `planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `reponses_etudiants`
--
ALTER TABLE `reponses_etudiants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `reponses_possibles`
--
ALTER TABLE `reponses_possibles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `resultats_qcm`
--
ALTER TABLE `resultats_qcm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `absences`
--
ALTER TABLE `absences`
  ADD CONSTRAINT `absences_ibfk_1` FOREIGN KEY (`planning_id`) REFERENCES `planning` (`id`),
  ADD CONSTRAINT `absences_ibfk_2` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `cours`
--
ALTER TABLE `cours`
  ADD CONSTRAINT `cours_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`);

--
-- Contraintes pour la table `fichiers`
--
ALTER TABLE `fichiers`
  ADD CONSTRAINT `fichiers_ibfk_1` FOREIGN KEY (`id_cours`) REFERENCES `cours` (`id`);

--
-- Contraintes pour la table `forum_reponses`
--
ALTER TABLE `forum_reponses`
  ADD CONSTRAINT `forum_reponses_ibfk_1` FOREIGN KEY (`sujet_id`) REFERENCES `forum_sujets` (`id`),
  ADD CONSTRAINT `forum_reponses_ibfk_2` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `forum_sujets`
--
ALTER TABLE `forum_sujets`
  ADD CONSTRAINT `forum_sujets_ibfk_1` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `messages_prives`
--
ALTER TABLE `messages_prives`
  ADD CONSTRAINT `messages_prives_ibfk_1` FOREIGN KEY (`expediteur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_prives_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`id_prof`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `planning`
--
ALTER TABLE `planning`
  ADD CONSTRAINT `planning_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `planning_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reponses_etudiants`
--
ALTER TABLE `reponses_etudiants`
  ADD CONSTRAINT `reponses_etudiants_ibfk_1` FOREIGN KEY (`resultat_id`) REFERENCES `resultats_qcm` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reponses_etudiants_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  ADD CONSTRAINT `reponses_etudiants_ibfk_3` FOREIGN KEY (`reponse_id`) REFERENCES `reponses_possibles` (`id`);

--
-- Contraintes pour la table `reponses_possibles`
--
ALTER TABLE `reponses_possibles`
  ADD CONSTRAINT `reponses_possibles_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `resultats_qcm`
--
ALTER TABLE `resultats_qcm`
  ADD CONSTRAINT `resultats_qcm_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`),
  ADD CONSTRAINT `resultats_qcm_ibfk_2` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`),
  ADD CONSTRAINT `tests_ibfk_2` FOREIGN KEY (`createur_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
