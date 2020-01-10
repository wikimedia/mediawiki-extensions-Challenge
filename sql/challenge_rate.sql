CREATE TABLE /*_*/challenge_rate (
  challenge_rate_id int(11) NOT NULL PRIMARY KEY auto_increment,
  challenge_id int(11) NOT NULL default 0,
  challenge_rate_date varbinary(14) NOT NULL default '',
  challenge_rate_actor bigint unsigned NOT NULL,
  challenge_rate_submitter_actor bigint unsigned NOT NULL,
  challenge_rate_score int(11) NOT NULL default 0,
  challenge_rate_comment text NOT NULL
)/*$wgDBTableOptions*/;
