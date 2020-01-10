DROP SEQUENCE IF EXISTS challenge_rate_challenge_rate_id_seq CASCADE;
CREATE SEQUENCE challenge_rate_challenge_rate_id_seq MINVALUE 0 START WITH 0;

CREATE TABLE challenge_rate (
  challenge_rate_id INTEGER NOT NULL DEFAULT nextval('challenge_rate_challenge_rate_id_seq') PRIMARY KEY,
  challenge_id INTEGER NOT NULL DEFAULT 0,
  challenge_rate_date TIMESTAMPTZ NOT NULL DEFAULT now(),
  challenge_rate_actor INTEGER NOT NULL DEFAULT 0,
  challenge_rate_submitter_actor INTEGER NOT NULL DEFAULT 0,
  challenge_rate_score INTEGER NOT NULL DEFAULT 0,
  challenge_rate_comment TEXT
);
