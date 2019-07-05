CREATE TABLE /*_*/challenge (
  challenge_id int(11) NOT NULL PRIMARY KEY auto_increment,
  -- Challenger
  challenge_user_id_1 int(11) NOT NULL default 0,
  challenge_username1 varchar(255) NOT NULL default '',
  -- Challenged
  challenge_user_id_2 int(11) NOT NULL default 0,
  challenge_username2 varchar(255) NOT NULL default '',
  challenge_info varchar(200) NOT NULL default '',
  challenge_event_date varchar(15) default NULL,
  challenge_description text,
  challenge_win_terms varchar(200) NOT NULL default '',
  challenge_lose_terms varchar(200) NOT NULL default '',
  challenge_winner_user_id int(11) NOT NULL default 0,
  challenge_winner_username varchar(255) NOT NULL default '',
  challenge_status int(11) NOT NULL default 0,
  -- The following two fields appear to be currently unused but were used in
  -- the past by Special:ChallengeAction...the question is: should we drop these
  -- or bring them back?
  challenge_accept_date datetime default NULL,
  challenge_complete_date datetime default NULL,
  challenge_date varbinary(14) NOT NULL default ''
)/*$wgDBTableOptions*/;

CREATE TABLE /*_*/challenge_rate (
  challenge_rate_id int(11) NOT NULL PRIMARY KEY auto_increment,
  challenge_id int(11) NOT NULL default 0,
  challenge_rate_date varbinary(14) NOT NULL default '',
  challenge_rate_user_id int(11) NOT NULL default 0,
  challenge_rate_username varchar(255) NOT NULL default '',
  challenge_rate_submitter_user_id int(11) NOT NULL default 0,
  challenge_rate_submitter_username varchar(255) NOT NULL default '',
  challenge_rate_score int(11) NOT NULL default 0,
  challenge_rate_comment text NOT NULL
)/*$wgDBTableOptions*/;

CREATE TABLE /*_*/challenge_user_record (
  challenge_record_user_id int(11) NOT NULL default 0,
  challenge_record_username varchar(255) NOT NULL default '',
  challenge_wins int(11) NOT NULL default 0,
  challenge_losses int(11) NOT NULL default 0,
  challenge_ties int(11) NOT NULL default 0
)/*$wgDBTableOptions*/;