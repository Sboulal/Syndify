# Syndify
sudo chown -R salmasb:salmasb /home/salmasb/Syndify/Syndify-Backend                
sudo chmod -R 775 /home/salmasb/Syndify/Syndify-Backend

belongsToMany()
L-Tables pivots (bp_to_key w bt_to_key) ma-kay7tajouch Model khass bihom, 7it Eloquent (ORM dyal Laravel) kay-gérerhom automatiquement b la méthode belongsToMany() lli zedt lik f l-Models l-fo9.



 sudo docker exec -it syndify_backend php artisan migrate:fresh --seed --seeder=SyndifyTestDataSeeder

  Dropping all tables ....................................................................................................... 33.17ms DONE

   INFO  Preparing database.  

  Creating migration table ................................................................................................... 8.19ms DONE

   INFO  Running migrations.  

  2026_04_13_141618_create_users_table ....................................................................................... 7.75ms DONE
  2026_04_13_141702_create_proprietes_table .................................................................................. 3.69ms DONE
  2026_04_13_141721_create_user_as_owner_table ............................................................................... 5.35ms DONE
  2026_04_13_141742_create_units_table ....................................................................................... 4.22ms DONE
  2026_04_13_141756_create_user_owner_unit_table ............................................................................. 3.86ms DONE
  2026_04_13_141813_create_cle_repartitions_table ............................................................................ 3.94ms DONE
  2026_04_13_141830_create_unit_to_key_table ................................................................................. 3.71ms DONE
  2026_04_13_144235_create_cache_table ....................................................................................... 7.60ms DONE
  2026_04_15_112855_create_exercices_table ................................................................................... 4.06ms DONE
  2026_04_15_112909_create_charge_previsionnelles_table ...................................................................... 4.10ms DONE
  2026_04_15_112922_create_charge_travauxes_table ............................................................................ 3.77ms DONE
  2026_04_15_112934_create_bp_to_key_table ................................................................................... 3.62ms DONE
  2026_04_15_112948_create_bt_to_key_table ................................................................................... 3.39ms DONE
  2026_04_15_155106_add_propriete_id_to_units_table .......................................................................... 2.35ms DONE
  2026_04_16_104420_create_encaissements_table ............................................................................... 5.96ms DONE
  2026_04_16_104437_create_depenses_table .................................................................................... 5.34ms DONE
  2026_04_16_104456_create_depense_for_owner_table ........................................................................... 3.59ms DONE
  2026_04_21_081921_create_appels_fonds_tables .............................................................................. 14.30ms DONE
  2026_04_21_150502_add_solde_to_user_as_owner_table ......................................................................... 1.18ms DONE
  2026_04_22_091803_add_otp_columns_to_users_table ........................................................................... 8.87ms DONE
  2026_04_22_094206_create_personal_access_tokens_table ...................................................................... 7.90ms DONE
  2026_04_23_114348_create_clotures_table .................................................................................... 5.02ms DONE


   INFO  Seeding database.  

🚀 BOOM! Données 100% liées et logiques!
⏳ Création des Documents PDF en cours...
🚀 BOOM! Documents générés et Data liée!
➜  Syndify git:(main) ✗ sudo docker exec -it syndify_backend php artisan tinker
Psy Shell v0.12.22 (PHP 8.4.20 — cli) by Justin Hileman
New PHP manual is available (latest: 3.0.5). Update with `doc --update-manual`

>  DB::table('users')->first()->email;

= "afay@example.org"

> 