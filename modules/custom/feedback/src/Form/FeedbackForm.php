<?php
/**
 * @file
 * Contains \Drupal\feedback\Form\FeedbackForm
 */
namespace Drupal\feedback\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FeedbackForm extends FormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'feedback_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['first_name'] = array(
            '#type' => 'textfield',
            '#title' => t('First name:'),
            '#required' => TRUE,
        );

        $form['last_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Last name:'),
            '#required' => TRUE,
        );

        $form['subject'] = array (
            '#type' => 'textfield',
            '#title' => t('Subject'),
        );

        $form['message'] = array (
            '#type' => 'textarea',
            '#title' => t('Message'),
            '#required' => TRUE,
        );

        $form['email'] = array (
            '#type' => 'email',
            '#title' => t('E-mail'),
            '#required' => TRUE,
        );


        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i', $form_state->getValue('email'))) {
            $form_state->setErrorByName('email', $this->t('E-mail field is invalid.'));
        }

    }
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $form_values = $form_state->getValues();
        $from = $form_values['email'];
        $to = Drupal::config('system.site')->get('mail');
        $params['subject'] = t($form_values['subject']);
        $params['body'] = t($form_values['message']);
        $result = simple_mail_send($from, $to, $params['subject'] , $params['body']);
        if ($result['result'] == true) {
            $this->messenger()->addMessage($this->t('Your message has been sent.'));
            Drupal::logger('feedback')->notice('E-Mail has been send to: '. $from);
            $response = $this->createContact($form_state);
            if($response) {
                $this->messenger->addMessage($this->t('Contact created.'));
            }
        }
        else {
            $this->messenger()->addMessage($this->t('There was a problem sending your message and it was not sent.'), 'error');
        }
    }

    public function createContact(FormStateInterface $form_state){
        $form_values = $form_state->getValues();
        $arr = array(
            'properties' => array(
                array(
                    'property' => 'email',
                    'value' => $form_values['email']
                ),
                array(
                    'property' => 'firstname',
                    'value' => $form_values['first_name']
                ),
                array(
                    'property' => 'lastname',
                    'value' => $form_values['last_name']
                )
            )
        );
        $post_json = json_encode($arr);
        $hapikey = 'ab0b93e5-778f-4085-914c-98bd3c0e9b7a';
        $endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=' . $hapikey;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
        echo "curl Errors: " . $curl_errors;
        echo "\nStatus code: " . $status_code;
        echo "\nResponse: " . $response;
        return $response;
    }
}