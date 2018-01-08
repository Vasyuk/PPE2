<?php
/**
 * Page d'accueil de l'application web AppliFrais
 * @package default
 * @todo  RAS
 */
  $repInclude = './include/';
  require($repInclude . "_init.inc.php");

  // page inaccessible si visiteur non connecté
  if ( ! estVisiteurConnecte() )
  {
      header("Location: cSeConnecter.php");
  }else{
    $idUser = obtenirIdUserConnecte() ;
    $lgUser = obtenirDetailVisiteur($idConnexion, $idUser);
    $admin = $lgUser['admin'];
      if( $admin == 0){
        header("Location:cAccueil.php");
      }
  }
  require($repInclude . "_entete.inc.php");
  $tabQteEltsForfait=lireDonneePost("txtEltsForfait", "");
  $moisSaisi=lireDonnee("lstMois", "");
  $utilSaisi=lireDonnee("lstUtil", "");
  $etape=lireDonneePost("etape","");
  $etapeSaisie=lireDonneePost("etapeSaisie","");
  $etapeGet =lireDonnee("etapeGet","");
  $idLigneHF = lireDonnee("idLigneHF", "");
  if ($etape != "demanderConsult" && $etape != "validerConsult" && $etapeSaisie != "validerSaisie") {
      // si autre valeur, on considère que c'est le début du traitement
      $etape = "demanderConsult";
  }
  if ($etape == "validerConsult") { // l'utilisateur valide ses nouvelles données
      // vérification de l'existence de la fiche de frais pour le mois demandé
      $existeFicheFrais = existeFicheFrais($idConnexion, $moisSaisi, $utilSaisi);
      // si elle n'existe pas, on la crée avec les élets frais forfaitisés à 0
      if ( !$existeFicheFrais ) {
          ajouterErreur($tabErreurs, "Il n'y a aucune fiche de frais pour ce utilisateur");
      }
      else {
          // récupération des données sur la fiche de frais demandée
          $tabFicheFrais = obtenirDetailFicheFrais($idConnexion, $moisSaisi, $utilSaisi);
      }

  }elseif ($etapeSaisie == "validerSaisie") {

    // l'utilisateur valide les éléments forfaitisés
    // vérification des quantités des éléments forfaitisés
    $ok = verifierEntiersPositifs($tabQteEltsForfait);
    if (!$ok) {
        ajouterErreur($tabErreurs, "Chaque quantité doit être renseignée et numérique positive.");
    }
    else { // mise à jour des quantités des éléments forfaitisés
        modifierEltsForfait($idConnexion, $moisSaisi, $utilSaisi ,$tabQteEltsForfait);
        validerEltsForfait($idConnexion, $moisSaisi, $utilSaisi);
        echo validerEltsForfait($idConnexion, $moisSaisi, $utilSaisi);
        $etape = "validerConsult";
    }

  }if ($etapeGet == "validerSuppressionLigneHF") {
      refuserLigneHF($idConnexion, $idLigneHF);
      $etape = "validerConsult";
  }
?>

<div id="contenu">
    <h2>Fiches de frais</h2>
    <form action="" method="post">
    <div class="corpsForm">
        <input type="hidden" name="etape" value="validerConsult" />
    <p style = "text-align:center">
      <select id="lstUtil" name="lstUtil" title="Sélectionnez le nom souhaité pour la fiche de frais">
          <?php
              $req = obtenirReqUsers();
              $idJeuMois = mysqli_query($idConnexion,$req);
              $lgMois = mysqli_fetch_assoc($idJeuMois);

              while ( is_array($lgMois) ) {
                $id = $lgMois["id"];
                $nom = $lgMois["nom"];
                $prenom = $lgMois["prenom"];
                $util = $lgMois["id"];
          ?>
          <option value="<?php echo $id ?>"<?php if ($utilSaisi == $id) { ?> selected="selected"<?php } ?>><?php echo  $nom?> <?php echo $prenom ?></option>

          <?php
                  $lgMois = mysqli_fetch_assoc($idJeuMois);
              }
              mysqli_free_result($idJeuMois);
          ?>

      </select>
      <select id="lstMois" name="lstMois" title="Sélectionnez le mois souhaité pour la fiche de frais">
          <?php
              // on propose tous les mois pour lesquels le visiteur a une fiche de frais
              $req = obtenirReqMoisFicheFrais($util);
              $idJeuMois = mysqli_query($idConnexion,$req);
              $lgMois = mysqli_fetch_assoc($idJeuMois);
              while ( is_array($lgMois) ) {
                  $mois = $lgMois["mois"];
                  $noMois = intval(substr($mois, 4, 2));
                  $annee = intval(substr($mois, 0, 4));
          ?>
          <option value="<?php echo $mois; ?>"<?php if ($moisSaisi == $mois) { ?> selected="selected"<?php } ?>><?php echo obtenirLibelleMois($noMois) . " " . $annee; ?></option>
          <?php
                  $lgMois = mysqli_fetch_assoc($idJeuMois);
              }
              mysqli_free_result($idJeuMois);
          ?>
      </select>
    </p>
    </div>
    <div class="piedForm">
    <p>
      <input  class = "btn btn-primary" id="ok" type="submit" value="Valider" size="20"
             title="Demandez à consulter cette fiche de frais" />
    </p>
    </div>

    </form>
    <?php

    // demande et affichage des différents éléments (forfaitisés et non forfaitisés)
    // de la fiche de frais demandée, uniquement si pas d'erreur détecté au contrôle
        if ($etape == "validerConsult" ) {
            if ( nbErreurs($tabErreurs) > 0 ) {
                echo toStringErreurs($tabErreurs) ;
            }
            else {



    if ($etapeGet == "validerSuppressionLigneHF"){
    ?>
    <div class = "container">
      <div class = "row">
        <p class="info">Les modifications de la fiche de frais hors forfait ont bien été enregistrées </p>
    <?php
    }
    ?>
          <table class="listeLegere">
                   <tr>
                      <th class="date">Date</th>
                      <th class="libelle">Libellé</th>
                      <th class="montant">Montant</th>
                      <th class="action">&nbsp;</th>
                   </tr>
      <?php
                  // demande de la requête pour obtenir la liste des éléments hors
                  // forfait du visiteur connecté pour le mois demandé
                  $req = obtenirReqEltsHorsForfaitFicheFrais($moisSaisi, $utilSaisi);
                  $idJeuEltsHorsForfait = mysqli_query( $idConnexion,$req);
                  $lgEltHorsForfait = mysqli_fetch_assoc($idJeuEltsHorsForfait);

                  // parcours des éléments hors forfait
                  while ( is_array($lgEltHorsForfait) ) {
                  ?>
                      <tr>
                         <td><?php echo $lgEltHorsForfait["date"] ; ?></td>
                         <td><?php echo filtrerChainePourNavig($lgEltHorsForfait["libelle"]) ; ?></td>
                         <td><?php echo $lgEltHorsForfait["montant"] ; ?></td>
                         <td><a href="?etapeGet=validerSuppressionLigneHF&amp;idLigneHF=<?php echo $lgEltHorsForfait["id"]; ?>
                           &amp;lstMois=<?php echo $moisSaisi ;?>&amp;lstUtil=<?php echo $utilSaisi ;?>"
                                onclick="return confirm('Voulez-vous vraiment refuser cette ligne de frais hors forfait ?');"
                                title="Refuser la ligne de frais hors forfait">REFUSE</a></td>
                      </tr>
                  <?php
                      $lgEltHorsForfait = mysqli_fetch_assoc($idJeuEltsHorsForfait);
                  }
                  mysqli_free_result($idJeuEltsHorsForfait);
        ?>
          </table>
          <form action="" method="post">
          <div class="corpsForm">
            <input type="hidden" name="etapeSaisie" value="validerSaisie" />
            <input type="hidden" name="lstMois" value="<?php echo $moisSaisi ; ?>" />
            <input type="hidden" name="lstUtil" value="<?php echo $utilSaisi ;?>" />
            <fieldset>
              <legend>Eléments forfaitisés
              </legend>
        <?php
              // demande de la requête pour obtenir la liste des éléments
              // forfaitisés du visiteur connecté pour le mois demandé
              $req = obtenirReqEltsForfaitFicheFrais($moisSaisi, $utilSaisi);
              $idJeuEltsFraisForfait = mysqli_query($idConnexion, $req);
              echo mysqli_error($idConnexion);
              $lgEltForfait = mysqli_fetch_assoc($idJeuEltsFraisForfait);
              while ( is_array($lgEltForfait) ) {
                  $idFraisForfait = $lgEltForfait["idFraisForfait"];
                  $libelle = $lgEltForfait["libelle"];
                  $quantite = $lgEltForfait["quantite"];
              ?>

                <label for="<?php echo $idFraisForfait ?>"><?php echo $libelle; ?></label>
                <input type="text" id="<?php echo $idFraisForfait ?>"
                      name="txtEltsForfait[<?php echo $idFraisForfait ?>]"
                      size="10" maxlength="5"
                      title="Entrez la quantité de l'élément forfaitisé"
                      value="<?php echo $quantite; ?>" />

              <?php
                  $lgEltForfait = mysqli_fetch_assoc($idJeuEltsFraisForfait);
              }
              mysqli_free_result($idJeuEltsFraisForfait);
              ?>
            </fieldset>
            <div class="piedForm">
              <input class = "btn btn-primary"  id="ok" type="submit" value="Valider" size="20"
                     title="Enregistrer les nouvelles valeurs des éléments forfaitisés" />
            </div>
            </form>
        </div>

    <?php
      }
    }

    ?>
      </div>
</div>
</div>






<?php
  require($repInclude . "_pied.inc.html");
  require($repInclude . "_fin.inc.php");
?>
