<?php
/**
 * Created by PhpStorm.
 * User: alessio
 * Date: 29/03/2015
 * Time: 09:10
 */
function tcmp_ui_feedback() {
    global $tcmp;

    $tcmp->Form->prefix='Feedback';
    if($tcmp->Check->nonce('tcmp_feedback', 'tcmp_feedback')) {
        $tcmp->Check->email('email');
        $tcmp->Check->value('body');

        if(!$tcmp->Check->hasErrors()) {
            $tcmp->Options->setFeedbackEmail($tcmp->Check->of('email'));
            $id=-1;
            if($tcmp->Check->of('track', 0)) {
                $id=$tcmp->Tracking->sendTracking(TRUE);
            }
            $tcmp->Check->data['tracking_id']=$id;
            $data=$tcmp->Utils->remotePost('feedback', $tcmp->Check->data);
            if($data) {
                $tcmp->Options->pushSuccessMessage('FeedbackSuccess');
            } else {
                $tcmp->Options->pushErrorMessage('FeedbackError');
            }
        }
    }
    ?>
    <br>
    <h2><?php $tcmp->Lang->P('FeedbackHeader')?></h2>
    <?php
    $tcmp->Options->writeMessages();

    $tcmp->Form->formStarts();
    $tcmp->Form->text('email', $tcmp->Options->getFeedbackEmail());
    $tcmp->Form->textarea('body', '', array('rows'=>5));
    $tcmp->Form->checkbox('track');

    $tcmp->Form->nonce('tcmp_feedback', 'tcmp_feedback');
    $tcmp->Form->submit('Send');
    $tcmp->Form->formEnds();
}