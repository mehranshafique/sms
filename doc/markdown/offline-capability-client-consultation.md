# DigiteX — Capacité hors ligne (Offline)

**Document de consultation client**  
**Integrale Plus / DigiteX** — Partenaires écoles (RDC et zone francophone)

**Objet :** recueillir vos avis et priorités avant de développer le mode hors ligne.  
**Contexte :** lors d’inscriptions élèves, une connexion Internet très faible a bloqué le travail. Nous souhaitons que les écoles puissent **continuer à travailler**, puis **synchroniser** quand le réseau revient.

**Date :** septembre 2026  
**Statut :** proposition — **aucune décision technique figée** ; vos retours orientent le cahier des charges.

---

## 1. Situation actuelle

DigiteX fonctionne aujourd’hui **en ligne** (navigateur ou PWA légère).

| Élément | État aujourd’hui |
| --- | --- |
| Application web DigiteX | Nécessite Internet pour enregistrer, payer, lister |
| PWA (installation sur téléphone / PC) | Existe, mais hors ligne elle n’affiche qu’une page « hors connexion » |
| Bornes / terminaux de présence | Déjà une logique de **synchronisation par lots** côté matériel |
| Inscription élève, frais, listes | **Pas encore** utilisables sans Internet |

**Objectif cible :** permettre aux secrétariats de **saisir le travail localement**, puis d’**envoyer les données** dès qu’une bonne connexion est disponible.

---

## 2. Ce que « hors ligne » peut signifier

Trois niveaux possibles (du plus simple au plus ambitieux) :

1. **Capture hors ligne + sync plus tard** — remplir des formulaires sur l’appareil ; envoi automatique ou manuel quand le réseau revient.  
2. **Opérations clés hors ligne** — inscription, présences, éventuellement paiements en brouillon ; le reste reste en ligne.  
3. **DigiteX presque entièrement hors ligne** — copie locale complète de l’école. Très lourd ; rarement justifié en phase 1.

**Recommandation Integrale Plus (à valider avec vous) :** commencer par le **niveau 1–2**, sur **inscription élèves** puis **présences**.

---

## 3. Toutes les options possibles (avec avantages / inconvénients)

### Option A — PWA hors ligne + file d’attente (recommandée pour démarrer)

**Principe :** DigiteX s’installe comme une « app » dans Chrome / Edge (PC ou tablette de l’école). Les écrans et listes utiles (classes, sections) sont mis en cache. Les inscriptions (et plus tard les présences) sont sauvegardées **sur l’appareil**, puis synchronisées vers le serveur.

| Avantages | Inconvénients |
| --- | --- |
| Réutilise DigiteX web actuel (moins de double maintenance) | Première visite / préparation **en ligne** obligatoire pour télécharger classes et écrans |
| Fonctionne sur PC secrétariat et tablettes Android courantes | Photos élèves : upload différé ou sans photo hors ligne |
| Coût et délai plus raisonnables que une app native complète | Deux secrétaires hors ligne en même temps : risque de **doublons** à résoudre à la sync |
| Même expérience FR / EN que DigiteX | Limites de stockage navigateur si beaucoup de photos |
| Aligné avec la PWA déjà en place | Pas magique sur iPhone sans étapes d’installation |

**Idéal si :** vous voulez un résultat utile rapidement, sur les PC déjà utilisés dans les bureaux.

---

### Option B — Application mobile Android dédiée (Flutter / natif) avec mode offline

**Principe :** une application « DigiteX École » installée depuis un fichier APK ou un store, avec base locale et sync.

| Avantages | Inconvénients |
| --- | --- |
| Sensation « vraie app », souvent plus robuste hors ligne | Coût et délai **plus élevés** (nouvelle app + APIs + maintenance) |
| Meilleur contrôle stockage, caméra, notifications | Double écran à former (web + app) sauf si on remplace le web pour ces tâches |
| Peut partager la logique sync avec une future app parents | iOS (iPhone) = contraintes supplémentaires (compte Apple, délais) |
| Utile si le secrétariat travaille surtout sur téléphone | Déploiement APK hors store à organiser école par école |

**Idéal si :** vos partenaires refusent le navigateur et veulent uniquement le téléphone / tablette.

---

### Option C — Enveloppe « app » autour du web (Capacitor / TWA)

**Principe :** même DigiteX web, empaqueté comme application Android (icône sur l’écran d’accueil), avec les mêmes mécanismes offline que l’option A.

| Avantages | Inconvénients |
| --- | --- |
| Une seule base de code web + packaging | Dépend encore des limites du navigateur embarqué |
| Distribution plus « app store / APK » | Moins flexible qu’une app Flutter 100 % native pour le hors ligne profond |
| Bon compromis marketing (« nous avons une app ») | Effort d’empaquetage et de mises à jour à prévoir |

**Idéal si :** vous voulez l’option A **et** une icône installable type application.

---

### Option D — DigiteX local sur un PC serveur d’école (mini-serveur)

**Principe :** un ordinateur dans l’école héberge une copie locale ; synchronisation vers le cloud DigiteX (e-digitex.com) quand Internet revient.

| Avantages | Inconvénients |
| --- | --- |
| Très résilient si Internet de l’école est souvent coupé | Installation, sauvegarde, sécurité et support **complexes** |
| Plusieurs postes du LAN peuvent travailler sans Internet public | Coût matériel + formation technique |
| Données restent sur place en cas de panne longue | Risque de divergence / conflits de sync importants |
| | Peu adapté au modèle SaaS multi-écoles actuel sans refonte |

**Idéal si :** une école a un informaticien dédié et une panne Internet très longue et fréquente. **Non recommandé** comme première étape pour toutes les écoles.

---

### Option E — Formulaire papier / Excel + import ultérieur

**Principe :** pendant la coupure, saisie Excel ou fiches ; import CSV dans DigiteX plus tard.

| Avantages | Inconvénients |
| --- | --- |
| Aucun développement (ou très léger : modèle Excel + import) | Erreurs de saisie, doublons, photos manquantes |
| Immédiatement utilisable | Double travail et formation Excel |
| Utile en secours d’urgence | Pas une vraie expérience DigiteX |

**Idéal si :** besoin d’un **plan B immédiat** en attendant le vrai mode hors ligne.

---

### Option F — Mode « brouillon cloud » seulement (sans vrai offline)

**Principe :** améliorer DigiteX en ligne pour mieux tolérer le réseau faible (auto-save, reprise de formulaire, messages clairs) **sans** stockage local hors ligne.

| Avantages | Inconvénients |
| --- | --- |
| Plus simple et moins cher | **Ne résout pas** une coupure totale d’Internet |
| Améliore déjà les journées « Internet faible mais pas zéro » | Inscription impossible si le réseau tombe vraiment |
| Complémentaire aux options A–C | |

**Idéal si :** le problème principal est la lenteur, pas l’absence totale de réseau. **Souvent utile en complément** de A.

---

## 4. Comparatif synthétique

| Option | Effort | Délai indicatif | Couverture hors ligne | Recommandation |
| --- | --- | --- | --- | --- |
| A — PWA + sync | Moyen | Quelques semaines (phase 1) | Élevée sur PC/tablette | **Priorité 1** |
| B — App Android native | Élevé | Plusieurs mois | Très élevée sur mobile | Priorité 2 si besoin mobile fort |
| C — Web empaqueté | Moyen+ | Après A ou avec A | Comme A | Option packaging |
| D — Serveur local école | Très élevé | Long | Très élevée sur site | Cas exceptionnels |
| E — Excel / papier | Faible | Immédiat | Secours seulement | Plan B court terme |
| F — Tolérance réseau faible | Faible–moyen | Court | Partielle | **Complément** de A |

*Les délais sont indicatifs et dépendent du périmètre exact validé avec vous.*

---

## 5. Périmètre fonctionnel proposé (à cocher avec le client)

Que doit fonctionner **sans Internet** en priorité ?

- [ ] Inscription / mise à jour élève (identité, classe, parent)  
- [ ] Présences de classe (marquage P / A / R / J)  
- [ ] Présences borne / NFC (déjà partiellement prévu côté matériel)  
- [ ] Paiements / encaissements (brouillon puis sync)  
- [ ] Consultation listes élèves / soldes (lecture seule hors ligne)  
- [ ] Autre : ________________________________

Que peut **rester obligatoire en ligne** au début ?

- [ ] Bulletins / impressions officielles  
- [ ] WhatsApp / SMS  
- [ ] Facturation abonnement DigiteX  
- [ ] Configuration école / rôles  

---

## 6. Règles métier à décider avec vous

Ces points évitent les mauvaises surprises à la synchronisation :

1. **Numéro matricule** — généré **au moment de la sync** sur le serveur (évite deux matricules identiques créés hors ligne).  
2. **Doublons** — si le même élève est saisi deux fois hors ligne : l’école valide manuellement laquelle garder.  
3. **Photos** — autorisées hors ligne (sync plus lourde) ou uniquement en ligne ?  
4. **Qui a le droit** de travailler hors ligne ? (Admin école / secrétariat seulement ?)  
5. **Plusieurs appareils** — un seul PC « registre offline » par école au début, ou plusieurs ?  
6. **Taille des lots** — sync par paquets (ex. 20 élèves) quand la connexion est bonne.

---

## 7. Proposition de feuille de route (si option A validée)

**Phase 1 — Inscription élèves hors ligne**  
Préparation des listes (classes/sections) en ligne → saisie hors ligne → file « En attente de sync » → envoi quand le réseau est bon.

**Phase 2 — Présences hors ligne**  
Même mécanisme que la phase 1 (proche de la sync déjà utilisée pour certains terminaux).

**Phase 3 — Options**  
Paiements brouillon, listes en lecture seule, packaging Android (option C) si demandé.

**En parallèle (option F) :**  
Améliorer les formulaires en ligne (sauvegarde partielle, messages réseau, reprise après erreur) pour les journées « Internet très lent ».

---

## 8. Risques et points de vigilance

- Données hors ligne = responsabilité de **ne pas formater / perdre** l’appareil avant sync.  
- Formation courte nécessaire (« Enregistrer localement » vs « Synchroniser »).  
- La sync nécessite quand même **un moment de bonne connexion**.  
- DigiteX cloud reste la **référence officielle** après sync réussie.

---

## 9. Votre avis — formulaire de retour

Merci de répondre (ou annoter ce PDF) et de nous renvoyer le document.

**École / organisation :** ________________________________

**Nom et fonction :** ________________________________

**Date :** ________________________________

### 9.1 Quelle option préférez-vous en premier ?

- [ ] A — PWA + synchronisation (recommandée)  
- [ ] B — Application Android dédiée  
- [ ] C — DigiteX web en « application » empaquetée  
- [ ] D — Serveur local dans l’école  
- [ ] E — Excel / papier en secours seulement  
- [ ] F — Améliorer d’abord le mode « Internet faible » (sans vrai offline)  
- [ ] Combinaison : A + F / autre : ____________

### 9.2 Priorité métier n°1 hors ligne

- [ ] Inscriptions élèves  
- [ ] Présences  
- [ ] Paiements  
- [ ] Autre : ____________

### 9.3 Environnement typique de l’école

- [ ] PC Windows au secrétariat  
- [ ] Tablettes Android  
- [ ] Téléphones Android du personnel  
- [ ] iPhone / iPad  
- [ ] Autre : ____________

### 9.4 Qualité Internet habituelle

- [ ] Souvent bonne  
- [ ] Souvent lente mais présente  
- [ ] Coupures fréquentes  
- [ ] Parfois une journée entière sans réseau  

### 9.5 Budget / urgence

- [ ] Urgent (besoin dès le prochain trimestre)  
- [ ] Important mais planifiable  
- [ ] Souhaitable à moyen terme  

**Commentaires libres :**

________________________________________________________________

________________________________________________________________

________________________________________________________________

________________________________________________________________

---

## 10. Prochaine étape

1. Retour de ce document (cases cochées + commentaires).  
2. Atelier court (visio ou présentiel) pour figer le **périmètre Phase 1**.  
3. Devis / planning de réalisation sur la base de l’option choisie.  

**Contact projet DigiteX / Integrale Plus :** à compléter par l’équipe commerciale.

---

*Document de consultation — ne constitue pas un engagement de délai tant que le périmètre et l’option ne sont pas validés par écrit.*
