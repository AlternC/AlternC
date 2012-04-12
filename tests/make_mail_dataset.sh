#!/bin/sh

# TODO Traduction.
# Script permettant de générer un jeu de données pour tester différents cas sur les mails.
#
# Utilisation :
#     make_mail_dataset.sh > dataset.sql

# Domaine sur lequel porte le jeu de données.
DOMAIN="test22.com"

# Identifiant du domaine.
DOMAIN_ID=2000

# Mot de passe des comptes.
PASSWORD="password"

# Delivery des mailbox
MAILBOX_DELIVERY="dovecot"

# Delivery des listes
MAILMAN_DELIVERY="mailman"

# Fonction pour ajouter la clause where
append_from_address(){
    username="$1"

    echo "FROM address WHERE address.domain_id = $DOMAIN_ID AND address.address = '$username';"
}

# Fonction permettant d'insérer une entrée dans la table address
insert_address(){
    username="$1"

    echo "INSERT INTO address (domain_id, address, password) VALUES ($DOMAIN_ID, '$username', md5('$PASSWORD'));"
}

# Fonction permettant d'insérer une entrée dans la table recipient
insert_recipient(){
    username="$1"
    alias="$2"

    echo "INSERT INTO recipient (address_id, recipients) SELECT address.id AS address_id, '$alias' AS recipients"
    append_from_address "$username"
}

# Fonction permettant d'insérer dans la table mailbox
insert_mailbox(){
    username="$1"

    echo "INSERT INTO mailbox (address_id, path, delivery) SELECT address.id AS address_id, '$DOMAIN/$username' AS path, '$MAILBOX_DELIVERY' AS delivery"
    append_from_address "$username"
}

# Fonction permettant d'insérer dans la table mailman
insert_mailman(){
    listname="$1"

    echo "INSERT INTO mailman (address_id, delivery) SELECT address.id AS address_id, '$MAILMAN_DELIVERY' AS delivery"
    append_from_address "$username"
}

# Fonction permettant de rajouter des destinataires supplémentaires sur une adresse
append_recipients(){
    username="$1"
    shift

    for i in $*; do
       insert_recipient "$username" "$i"
    done
   
}

# Fonction permettant d'ajouter une adresse avec un alias avec des destinataires supplémentaires
add_recipient(){
    alias="alias$1"
    shift

    begin
    insert_address "$alias"
    append_recipients "$alias" $*
    commit
}

# Fonction permettant d'ajouter une mailbox avec des destinataires supplémentaires
add_mailbox(){
    username="mailbox$1"
    shift

    begin
    insert_address "$username"
    insert_mailbox "$username"
    append_recipients "$username" $*
    commit
}

# Fonction permettant d'ajouter une liste de diffusion avec des destinataires supplémentaires
add_list(){
    listname="list$1"
    shift

    begin
    insert_address "$listname"
    insert_mailman "$listname"
    append_recipients "$listname" $*
    commit
}

# Fonction permettant d'ajouter une clause de début de transaction
begin(){
    echo ""
    echo "BEGIN;"
}

# Fonction permettant d'ajouter une clause de fin de transaction
commit(){
    echo "COMMIT;"
}

# Ajout du domaine
begin
echo "INSERT INTO domaines (compte, domaine, dns_result) VALUES ($DOMAIN_ID, '$DOMAIN', '0');"
commit

# Cas simples
# alias00@$DOMAIN : alias vers recipient00@example.com
add_recipient "00" "recipient00@example.com"

# mailbox00@$DOMAIN : mailbox locale
add_mailbox "00"

# list00@$DOMAIN : liste de diffusion
add_list "00"

# Cas complexes de niveau 1
# alias10@$DOMAIN : alias vers alias00@$DOMAIN
add_recipient "10" "alias00@$DOMAIN"

# alias11@$DOMAIN : alias vers mailbox00@$DOMAIN
add_recipient "11" "mailbox00@$DOMAIN"

# alias12@$DOMAIN : alias vers list00@$DOMAIN
add_recipient "12" "list00@$DOMAIN"

# mailbox10@$DOMAIN : mailbox locale avec distribution supplémentaire vers recipient00@example.com (mailbox + alias00)
add_mailbox "10" "alias00@$DOMAIN"

# list10@$DOMAIN : liste de diffusion avec distribution supplémentaire vers recipient00@example.com (list + alias00)
add_list "10" "alias00@$DOMAIN"

# Cas complexes de niveau 2
# alias20@$DOMAIN : alias vers recipient00@example.com et alias00@$DOMAIN (alias00 + alias10)
add_recipient "20" "recipient00@example.com" "alias00@$DOMAIN"

# alias21@$DOMAIN : alias vers recipient00@example.com et mailbox00@$DOMAIN (alias00 + alias11)
add_recipient "21" "recipient00@example.com" "mailbox00@$DOMAIN"

# alias22@$DOMAIN : alias vers recipient00@example.com et list00@$DOMAIN (alias00 + alias12)
add_recipient "22" "recipient00@example.com" "list00@$DOMAIN"

# mailbox20@$DOMAIN : mailbox locale avec distribution supplémentaire vers alias00@$DOMAIN (mailbox + alias10)
add_mailbox "20" "alias00@$DOMAIN"

# mailbox21@$DOMAIN : mailbox locale avec distribution supplémentaire vers mailbox00@$DOMAIN (mailbox + alias11)
add_mailbox "21" "mailbox00@$DOMAIN"

# mailbox22@$DOMAIN : mailbox locale avec distribution supplémentaire vers list00@$DOMAIN (mailbox + alias12)
add_mailbox "22" "list00@$DOMAIN"

# list20@$DOMAIN : liste de diffusion avec distribution supplémentaire vers alias00@$DOMAIN (list + alias10)
add_list "20" "alias00@$DOMAIN"

# list21@$DOMAIN : liste de diffusion avec distribution supplémentaire vers mailbox00@$DOMAIN (list + alias11)
add_list "21" "mailbox00@$DOMAIN"

# list22@$DOMAIN : liste de diffusion avec distribution supplémentaire vers list00@$DOMAIN (list + alias12)
add_list "22" "list00@$DOMAIN"

# Cas complexes de niveau 3
# alias30@$DOMAIN : alias vers alias00@$DOMAIN, mailbox00@$DOMAIN et test00@$DOMAIN (alias10 + alias11 + alias12)
add_recipient "30" "alias00@$DOMAIN" "mailbox00@$DOMAIN" "test00@$DOMAIN"

# mailbox30@$DOMAIN : mailbox locale avec distribution supplémentaire vers recipient00@example.com et alias00@$DOMAIN (mailbox + alias00 + alias10)
add_mailbox "30" "recipient00@example.com" "alias00@$DOMAIN"

# mailbox31@$DOMAIN : mailbox locale avec distribution supplémentaire vers recipient00@example.com et mailbox00@$DOMAIN (mailbox + alias00 + alias11)
add_mailbox "31" "recipient00@example.com" "mailbox00@$DOMAIN"

# mailbox32@$DOMAIN : mailbox locale avec distribution supplémentaire vers recipient00@example.com et list00@$DOMAIN (mailbox + alias00 + alias12)
add_mailbox "32" "recipient00@example.com" "list00@$DOMAIN"

# list30@$DOMAIN : liste de diffusion avec distribution supplémentaire vers recipient00@example.com et alias00@$DOMAIN (list + alias00 + alias10)
add_list "30" "recipient00@example.com" "alias00@$DOMAIN"

# list31@$DOMAIN : liste de diffusion avec distribution supplémentaire vers recipient00@example.com et mailbox00@$DOMAIN (list + alias00 + alias11)
add_list "31" "recipient00@example.com" "mailbox00@$DOMAIN"

# list32@$DOMAIN : liste de diffusion avec distribution supplémentaire vers recipient00@example.com et list00@$DOMAIN (list + alias00 + alias12)
add_list "32" "recipient00@example.com" "list00@$DOMAIN"

# Cas complexe de niveau 4
# alias40@$DOMAIN : alias vers recipient00@example.com, alias00@$DOMAIN, mailbox00@$DOMAIN et list00@$DOMAIN (alias00 + alias10 + alias11 + alias12)
add_recipient "40" "recipient00@example.com" "alias00@$DOMAIN" "mailbox00@$DOMAIN" "list00@$DOMAIN"

# mailbox40@$DOMAIN : mailbox locale avec distribution supplémentaire vers alias00@$DOMAIN, mailbox00@$DOMAIN et list00@$DOMAIN (mailbox + alias10 + alias11 + alias12) 
add_mailbox "40" "alias00@$DOMAIN" "mailbox00@$DOMAIN" "list00@$DOMAIN"

# list40@$DOMAIN : liste de diffusion avec distribution supplémentaire vers alias00@$DOMAIN, mailbox00@$DOMAIN et list00@$DOMAIN (list + alias10 + alias11 + alias12)
add_list "40" "alias00@$DOMAIN" "mailbox00@$DOMAIN" "list00@$DOMAIN"

# Cas complexe de niveau 5
# mailbox50@$DOMAIN : mailbox locale avec distribution supplémentaire vers recipient00@example.com, alias00@$DOMAIN, mailbox00@$DOMAIN et list00@$DOMAIN (mailbox + alias00 + alias10 + alias11 + alias12)
add_mailbox "50" "recipient00@example.com" "alias00@$DOMAIN" "mailbox00@$DOMAIN" "list00@$DOMAIN"

# list50@$DOMAIN : liste de diffusion avec distribution supplémentaire vers recipient00@example.com, alias00@$DOMAIN, mailbox00@$DOMAIN et list00@$DOMAIN (list + alias00 + alias10 + alias11 + alias12)
add_list "50" "recipient00@example.com" "alias00@$DOMAIN" "mailbox00@$DOMAIN" "list00@$DOMAIN"

