<?php
/**
 * @author Tim Lytle <tim@timlytle.net>
 */
class Feedback
{
    const COLLECTION = 'responses';

    /**
     * @var string
     */
    protected $from;

    /**
     * @var Nexmo
     */
    protected $nexmo;

    /**
     * @var MongoDB
     */
    protected $db;

    /**
     * @var MongoCollection
     */
    protected $collection;

    protected $questions = array(
        'recommend' => 'How likely are you to recommend this talk to your friends? (1-10)',
        'use'       => 'How likely are you to use SMS in the near future? (1-10)',
        'contact'   => 'If you would like more information, how can I reach you?'
    );

    /**
     * Create a new feedback service.
     *
     * @param Nexmo $nexmo
     * @param $from
     * @param MongoDB $db
     * @param string $collection
     */
    public function __construct(Nexmo $nexmo, $from, MongoDB $db, $collection = self::COLLECTION)
    {
        $this->nexmo = $nexmo;
        $this->from  = $from;

        $this->db = $db;
        $this->collection = $db->$collection;
    }

    /**
     * Process an inbound number.
     *
     * @param string $number
     * @param string $text
     */
    public function process($number, $text)
    {
        $response = $this->getResponse($number);
        $response = $this->addResponse($response, $text);

        //if there's a pending, send it
        if(!empty($response['pending'])){
            $this->sendSMS($number, $response['pending']);
        }

        //save the data
        $this->collection->update(array('number' => $number), $response);
    }

    /**
     * Get (or create) a response for a user.
     *
     * @param $number
     * @param DateTime $stamp
     */
    public function getResponse($number, MongoDate $stamp = null)
    {
        $this->log($number, 'looking for response');

        if(is_null($stamp)){
            $stamp = new MongoDate();
        }

        //look for current response
        $response = $this->collection->findOne(array('number' => $number));

        //create one if needed
        if(!$response){
            $this->log($number, 'creating new respone');
            $response = array(
                'number' => $number,
                'created' => $stamp,
                'questions' => $this->questions
            );

            $this->collection->insert($response);

            $response = $this->collection->findOne(array('number' => $number));
        }

        if(!$response){
            throw new RuntimeException('could not find response');
        }

        return $response;
    }

    /**
     * Add inbound text to the response document, and pick a new (if any) question to ask.
     *
     * @param array $response
     * @param string $text
     */
    public function addResponse($response, $text)
    {
        //is this the first answer
        if(empty($response['pending']) AND empty($response['initial'])){
            $this->log($response['number'], 'adding first answer');
            $response['initial'] = $text;
        } elseif(empty($response['pending'])) {
            $this->log($response['number'], 'adding additional');
            if(empty($response['additional'])){
                $response['additional'] = array();
            }
            $response['additional'][] = $text;

        } else {
            $this->log($response['number'], 'adding answer to: ' . $response['pending']);
            $response['answers'][$response['pending']] = array(
                'created' => new MongoDate(),
                'text'    => $text
            );
        }

        //are there more questions
        if($response['questions'] AND count($response['questions']) > 0){
            //ask random question
            $key = array_rand($response['questions']);
            $response['pending'] = $response['questions'][$key];
            unset($response['questions'][$key]);
            $this->log($response['number'], 'asking: ' . $response['pending']);
        } else {
            $this->log($response['number'], 'done with questions');
            unset($response['pending']);
        }

        return $response;
    }

    /**
     * Send a message to the number. Pretty simple really.
     *
     * @param $to
     * @param $text
     */
    protected function sendSMS($to, $text)
    {
        $this->log($to, 'sending message: ' . $text);
        $result = $this->nexmo->sendSMS($to, $this->from, $text);
        foreach($result->messages as $message){
            if(isset($message->{'error-text'})){
                $this->log($to, $message->{'error-text'});
            } else {
                $this->log($to, $message->{'message-id'});
            }
        }
    }

    /**
     * Simple log wrapper.
     *
     * @param string $number
     * @param string $text
     */
    protected function log($number, $text)
    {
        error_log("[$number] $text");
    }
}