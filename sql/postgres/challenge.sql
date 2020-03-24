DROP SEQUENCE IF EXISTS challenge_challenge_id_seq CASCADE;
CREATE SEQUENCE challenge_challenge_id_seq;

CREATE TABLE challenge (
  challenge_id INTEGER NOT NULL DEFAULT nextval('challenge_challenge_id_seq') PRIMARY KEY,
  challenge_challenger_actor INTEGER NOT NULL DEFAULT 0,
  challenge_challengee_actor INTEGER NOT NULL DEFAULT 0,
  challenge_info TEXT,
  challenge_event_date TIMESTAMPTZ NULL,
  challenge_description TEXT,
  challenge_win_terms TEXT,
  challenge_lose_terms TEXT,
  challenge_winner_actor INTEGER NOT NULL DEFAULT 0,
  challenge_status INTEGER NOT NULL DEFAULT 0,
  challenge_accept_date TIMESTAMPTZ NULL,
  challenge_complete_date TIMESTAMPTZ NULL,
  challenge_date TIMESTAMPTZ NOT NULL DEFAULT now()
);
