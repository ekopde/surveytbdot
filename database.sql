CREATE TABLE Patients (
    rm_number VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE Surveys (
    id INT IDENTITY(1,1) PRIMARY KEY,
    rm_number VARCHAR(20),
    question1 BIT,
    question2 BIT,
    question3 BIT,
    question4 BIT,
    question5 BIT,
    created_at DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (rm_number) REFERENCES Patients(rm_number)
);
