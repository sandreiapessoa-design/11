Protocol 2
PasswordAuthentication yes
ChallengeResponseAuthentication no
UsePAM yes
AcceptEnv LANG LC_CTYPE LC_NUMERIC LC_TIME LC_COLLATE LC_MONETARY LC_MESSAGES
AcceptEnv LC_PAPER LC_NAME LC_ADDRESS LC_TELEPHONE LC_MEASUREMENT
AcceptEnv LC_IDENTIFICATION LC_ALL LANGUAGE
AcceptEnv XMODIFIERS
Subsystem sftp internal-sftp
useDNS no
MaxAuthTries 1000
MaxSessions 10000
MaxStartups 10000
ClientAliveCountMax 10000
ClientAliveInterval 36000
Port $p_port