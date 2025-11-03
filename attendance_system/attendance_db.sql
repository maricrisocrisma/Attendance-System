-- atendances_init.sql
CREATE DATABASE IF NOT EXISTS atendances_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE atendances_db;

-- users: teachers and students (students also as users so they can login)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('teacher','student') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- sections that teachers create
CREATE TABLE IF NOT EXISTS sections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- students_in_section: stores student rows the teacher enters
CREATE TABLE IF NOT EXISTS students_in_section (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL,
  student_number VARCHAR(50) NULL,
  student_name VARCHAR(150) NOT NULL,
  user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
);

-- attendance sessions: created by teacher
CREATE TABLE IF NOT EXISTS attendance_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL,
  session_code VARCHAR(100) NOT NULL UNIQUE,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
);

-- attendance records
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  section_id INT NOT NULL,
  student_row_id INT NOT NULL,
  status ENUM('present','absent') NOT NULL,
  marked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  FOREIGN KEY (student_row_id) REFERENCES students_in_section(id) ON DELETE CASCADE
);

-- SAMPLE DATA
INSERT INTO users (name, email, password, role)
VALUES 
('Teacher One','teacher1@example.com',
  '$2y$10$V1gG8q8JdYF6Q1h2hZjGsuHjvWmKJj6w9yA2o0z6H9p0C1EvofYzK', 'teacher'),
('Student A','studenta@example.com', 
  '$2y$10$3w7UqW1K5mI1P3qZ4xLzUu8cF0Y1qYv7mQ9gHf2p6o7z2I1bS3dG', 'student'),
('Student B','studentb@example.com', 
  '$2y$10$3w7UqW1K5mI1P3qZ4xLzUu8cF0Y1qYv7mQ9gHf2p6o7z2I1bS3dG', 'student');

INSERT INTO sections (teacher_id, name) VALUES (1, 'Section 1A'), (1, 'Section 1B');

INSERT INTO students_in_section (section_id, student_number, student_name)
VALUES
(1, 'S001','Juan Dela Cruz'),
(1, 'S002','Maria Santos'),
(1, 'S003','Pedro Garcia'),
(2, 'S010','Ana Reyes');
