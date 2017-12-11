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
?>

<div id="contenu">
    <h2>Fiches de frais</h2>
    <form action="" method="post">
    <div class="corpsForm">
        <input type="hidden" name="etape" value="validerConsult" />
    <p style = "text-align:center">
      <select id="lstMois" name="lstMois" title="Sélectionnez le mois souhaité pour la fiche de frais">
          <?php
              // on propose tous les mois pour lesquels le visiteur a une fiche de frais
              $req = obtenirReqUsers();
              $idJeuMois = mysqli_query($idConnexion,$req);
              $lgMois = mysqli_fetch_assoc($idJeuMois);
              while ( is_array($lgMois) ) {
                  $nom = $lgMois["nom"];
          ?>
          <option value="<?php echo $lgMois; ?>"><?php echo $nom ?></option>

          <?php
                  $lgMois = mysqli_fetch_assoc($idJeuMois);
              }
              mysqli_free_result($idJeuMois);
          ?>
      </select>
      <select id="lstMois" name="lstMois" title="Sélectionnez le mois souhaité pour la fiche de frais">
          <?php
              // on propose tous les mois pour lesquels le visiteur a une fiche de frais
              $req = obtenirReqMoisFicheFrais(obtenirIdUserConnecte());
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
  require($repInclude . "_pied.inc.html");
  require($repInclude . "_fin.inc.php");
?>
