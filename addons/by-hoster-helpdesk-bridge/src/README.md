# By-Hoster Helpdesk Webhook Bridge

Auteur: By-Hoster  
Contact: t.vinsonneau@by-hoster.com

Cet addon est un squelette d'externalisation du flux d'emails entrants Helpdesk.
Le cœur applicatif délègue désormais la logique au service `App\\Services\\Helpdesk\\InboundEmailBridgeService`,
ce qui permet de migrer ensuite proprement cette logique vers un addon dédié.
