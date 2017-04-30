=== Mosaika : commissions WooCommerce ===
Contributors: pskli 
Tags: woocommerce, commission, referral, submission
Requires at least: 4.0
Tested up to: 4.7.4
Stable tag: trunk

Plugin permettant la création d'un système de prescription de produit / versement et usage de commissions.

== Description ==

Ce plugin permet d'une part d'afficher un formulaire permettant aux utilisateurs de proposer un produit WooCommerce pour qu'il soit en vente dans une boutique.
Une fois validé par l'administrateur et un produit proposé en vente, les prescripteurs (parrains) recevront des points à chaque achat de leur produit.
Ces points sont versés selon un pourcentage de commission, et si la date d'achat du produit est valide : en effet, dans l'administration des produits WooCommerce, l'administrateur peut définir le montant de la commission, l'identifiant de l'utilisateur parrain et la date de début et de fin de validité de commission. En dehors de ces dates, aucune commission ne sera versée.

Ce plugin a été créé dans le cadre d'une série de tutoriels disponible sur le blog Mosaika : https://mosaika.fr/guide/woocommerce-creer-systeme-commission-prescription/

== Installation ==

Installez le contenu de ce repo dans wp-content/plugins/msk_formulaire_proposer_produit ou uploadez son ZIP via la page Extensions dans l'admin WordPress.

Ensuite, il suffit d'inclure le shortcode [msk_formulaire_proposer_produit] sur une page pour afficher le formulaire de soumission d'un produit.