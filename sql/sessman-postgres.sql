
-- Extensions

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- sessman_user

CREATE TABLE sessman_user (
	id uuid NOT NULL DEFAULT uuid_generate_v4(),
	username varchar NOT NULL,
	secret varchar NOT NULL,
	firstname varchar NULL,
	lastname varchar NULL,
	active timestamp NULL DEFAULT now(),
	tokens bool NOT NULL DEFAULT false,
	sa bool NOT NULL DEFAULT false,
	CONSTRAINT sessman_user_pk PRIMARY KEY (id),
	CONSTRAINT sessman_user_un UNIQUE (username)
);

INSERT INTO sessman_user (username,secret,active,sa) VALUES ('admin', crypt('admin', gen_salt('bf')), now(), true);

-- sessman_group

CREATE TABLE sessman_group (
	id uuid NOT NULL DEFAULT uuid_generate_v4(),
	groupname varchar NOT NULL,
	ldapcn varchar NULL,
	radiusattr varchar NULL,
	CONSTRAINT sessman_group_pk PRIMARY KEY (id)
);

-- sessman_usergrups

CREATE TABLE sessman_usergroups (
	smuser uuid NOT NULL,
	smgroup uuid NOT NULL,
	CONSTRAINT sessman_usergroups_pk PRIMARY KEY (smuser, smgroup)
);

ALTER TABLE sessman_usergroups ADD CONSTRAINT sessman_usergroups_fk FOREIGN KEY (smuser) REFERENCES sessman_user(id);
ALTER TABLE sessman_usergroups ADD CONSTRAINT sessman_usergroups_fk_1 FOREIGN KEY (smgroup) REFERENCES sessman_group(id);

-- sessman_usertokens

CREATE TABLE sessman_usertokens (
	id uuid NOT NULL DEFAULT uuid_generate_v4(),
	smuser uuid NOT NULL,
	name varchar NULL,
	token varchar NOT NULL,
	expiry timestamp NULL,
	CONSTRAINT sessman_usertokens_pk PRIMARY KEY (id)
);

ALTER TABLE sessman_usertokens ADD CONSTRAINT sessman_usertokens_fk FOREIGN KEY (smuser) REFERENCES sessman_user(id);
