<?php
function tcmp_ui_faq() {
    global $tcmp;
    $i=1;
    while($tcmp->Lang->H('Faq.Question'.$i)) {
        $q=$tcmp->Lang->L('Faq.Question'.$i);
        $r=$tcmp->Lang->L('Faq.Response'.$i);
        ?>
        <p>
            <b><?php echo $q?></b>
            <br/>
            <?php echo $r?>
        </p>
        <?php
        ++$i;
    }
    ?>
    <h2><?php $tcmp->Lang->P('YouTubeVideo.Title') ?></h2>
    <?php
    $i=1;
    while($tcmp->Lang->H('YouTubeVideo.URL'.$i)) {
        $q=$tcmp->Lang->L('YouTubeVideo.URL'.$i);
        $r=$tcmp->Lang->L('YouTubeVideo.Description'.$i);
        ?>
        <p>
            <iframe width="350" height="210" src="https://www.youtube.com/embed/<?php echo $q?>"></iframe>
            <br/>
            <?php echo $r?>
        </p>
        <?php
        ++$i;
    }
}