CREATE TABLE /*_*/challenge (
  challenge_id int(11) NOT NULL PRIMARY KEY auto_increment,
  -- Actor ID of the person who sent the challenge
  challenge_challenger_actor bigint unsigned NOT NULL,
  -- Actor ID of the person who was challenged
  challenge_challengee_actor bigint unsigned NOT NULL,
  challenge_info varchar(200) NOT NULL default '',
  challenge_event_date varchar(15) default NULL,
  challenge_description text,
  challenge_win_terms varchar(200) NOT NULL default '',
  challenge_lose_terms varchar(200) NOT NULL default '',
  challenge_winner_actor bigint unsigned NOT NULL,
  challenge_status int(11) NOT NULL default 0,
  -- The following two fields appear to be currently unused but were used in
  -- the past by Special:ChallengeAction...the question is: should we drop these
  -- or bring them back?
  challenge_accept_date datetime default NULL,
  challenge_complete_date datetime default NULL,
  challenge_date varbinary(14) NOT NULL default ''
)/*$wgDBTableOptions*/;
