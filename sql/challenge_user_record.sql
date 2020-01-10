CREATE TABLE /*_*/challenge_user_record (
  challenge_record_actor bigint unsigned NOT NULL,
  challenge_wins int(11) NOT NULL default 0,
  challenge_losses int(11) NOT NULL default 0,
  challenge_ties int(11) NOT NULL default 0
)/*$wgDBTableOptions*/;
