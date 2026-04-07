CREATE DATABASE test_engine;
CREATE USER test_engine@localhost IDENTIFIED BY 'Pass1w0rd!';
GRANT ALL ON test_engine.* TO test_engine@localhost;

