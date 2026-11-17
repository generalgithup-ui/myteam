CREATE DATABASE IF NOT EXISTS team_assignment_m5
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE team_assignment_m5;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
  ip_hash CHAR(64) NOT NULL,
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  first_attempt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ip_hash)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_code VARCHAR(40) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  gender ENUM('Female','Male','Other','Prefer not to say') NOT NULL,
  phone VARCHAR(40) NULL,
  class_name VARCHAR(80) NOT NULL,
  photo_url VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_student_code (student_code),
  KEY idx_student_name (last_name, first_name),
  KEY idx_student_class (class_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(80) NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subject_code (code),
  UNIQUE KEY uq_subject_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teams (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  subject_id TINYINT UNSIGNED NOT NULL,
  team_name VARCHAR(100) NOT NULL,
  leader_student_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_team_subject_name (subject_id, team_name),
  KEY idx_team_leader (leader_student_id),
  CONSTRAINT fk_team_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
  CONSTRAINT fk_team_leader FOREIGN KEY (leader_student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS team_members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  team_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  member_role ENUM('leader','member') NOT NULL DEFAULT 'member',
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_team_member (team_id, student_id),
  KEY idx_member_student (student_id),
  CONSTRAINT fk_member_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
  CONSTRAINT fk_member_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assignments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  team_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  status ENUM('Not started','In progress','Submitted','Completed') NOT NULL DEFAULT 'Not started',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_assignment_team (team_id),
  CONSTRAINT fk_assignment_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NULL,
  action VARCHAR(40) NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_created (created_at),
  CONSTRAINT fk_activity_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO subjects (id, code, name, sort_order) VALUES
  (1, 'ADS', 'ADS', 1),
  (2, 'DC', 'DC', 2),
  (3, 'DB', 'DB', 3),
  (4, 'CA', 'CA', 4),
  (5, 'JAVA', 'JAVA', 5),
  (6, 'ENGLISH', 'ENGLISH', 6);
