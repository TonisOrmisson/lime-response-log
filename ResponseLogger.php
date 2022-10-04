<?php

use Yii;

class ResponseLogger extends PluginBase
{
    private $tableName = '{{response_log}}';
    static protected $description = 'Response logger';
    static protected $name = 'ResponseLogger';
    /** @var string */
    private $surveyId;

    private const EVENT_BEFORE_SURVEY_PAGE = 1;
    private const MOVE_NEXT = 1;
    private const MOVE_PREV = 2;


    public function init() {
        $this->subscribe('beforeActivate');
        $this->subscribe('saveSurveyForm');
        $this->subscribe('beforeSurveyPage');
    }


    public function beforeSurveyPage()
    {
        $event = $this->event;
        $this->surveyId = $event->get('surveyId');

        $key = 'survey_'.$this->surveyId;
        $surveyData = $_SESSION[$key] ?? null;

        if ($surveyData === null) {
            Yii::log('first page '. $this->surveyId,'info', __METHOD__);
            return;
        }
        $responseId = $_SESSION[$key]['srid'] ?? null;
        $step = $surveyData['step'] ?? null;
        $token = $surveyData['token'] ?? null;
        $removeKeys = ['YII_CSRF_TOKEN', 'ajax', 'fieldnames', 'LEMpostKey', 'token'];
        $log_data = new ArrayObject($_POST);
        foreach ($removeKeys as $key) {
            if(isset($log_data[$key])) {
                unset($log_data[$key]);
            }
        }
        $data = [
            'response_id' => $responseId,
            'step' => $step,
            'token' => $token,
            'data' => json_encode($surveyData),
            'move' => $_POST['move'] ?? null,
            'log_data' => json_encode($log_data),
        ];

        /** @var CHttpRequest $request */
        $request = Yii::app()->request;

        Yii::log(json_encode($log_data),'info', __METHOD__);
        Yii::log("Step " . $_POST['lastgroup'],'info', __METHOD__);
        Yii::log("Step " . $_POST['move'],'info', __METHOD__);

        Yii::log("Step " . $step,'info', __METHOD__);
        Yii::log("Token " . $token,'info', __METHOD__);
        Yii::log("Response id " . $responseId,'info', __METHOD__);
        $this->save($data);


    }

    private function save(array $data)
    {
        /** @var CDbConnection $db */
        $db = Yii::app()->db;
        /** @var CHttpRequest $request */
        $request = Yii::app()->request;
        $db->createCommand()->insert($this->tableName,
            [
                'survey_id' => $this->surveyId,
                'created' => date('Y-m-d H:i:s'),
                'response_id' => $data['response_id'],
                'token' => $data['token'],
                'step' => $data['step'],
                'event' => $this->eventId(),
                'move' => $this->moveId($data['move']),
                'ip' => $request->userHostAddress,
            ]
        );




    }



    public function beforeActivate()
    {
        if ($this->api->tableExists($this, $this->tableName)) {
            return;
        }
        /** @var CDbConnection $db */
        $db = Yii::app()->db;

        $db->createCommand()->createTable($this->tableName, ['id'=>'pk',
            'created'=>'datetime',
            'survey_id'=>'int(11) NOT NULL',
            'response_id'=>'int(11) NULL',
            'token'=>'varchar(36) NULL',
            'ip' => 'varchar(45) NULL',
            'step' => 'int(11) NULL',
            'event' => 'int(11) NULL',
            'move' => 'int(11) NULL',
            'log_data' => 'JSON DEFAULT NULL',
        ]);

        $db->createCommand()->createIndex('idx_response_survey', $this->tableName, ['survey_id']);
        $db->createCommand()->createIndex('idx_response_created', $this->tableName, ['created']);
        $db->createCommand()->createIndex('idx_response_response', $this->tableName, ['response_id']);
        $db->createCommand()->createIndex('idx_response_step', $this->tableName, ['step']);
        $db->createCommand()->createIndex('idx_response_event', $this->tableName, ['event']);
        $db->createCommand()->createIndex('idx_response_move', $this->tableName, ['move']);

        $db->createCommand()->createIndex('idx_response_sresponse', $this->tableName, ['survey_id', 'response_id']);
        $db->createCommand()->createIndex('idx_response_stoken', $this->tableName, ['survey_id', 'token']);
        $db->createCommand()->createIndex('idx_response_screated', $this->tableName, ['survey_id', 'created']);
        $db->createCommand()->createIndex('idx_response_sstep', $this->tableName, ['survey_id', 'step']);
        $db->createCommand()->createIndex('idx_response_smove', $this->tableName, ['survey_id', 'move']);
    }

    private function eventId() : int
    {
        switch ($this->event->getEventName()) {
            case 'beforeSurveyPage':
                return self::EVENT_BEFORE_SURVEY_PAGE;
            default:
                return 0;
        }
    }

    private function moveId(?string $move) : int
    {
        switch ($move) {
            case 'movenext':
                return self::MOVE_NEXT;
            case 'moveprev':
                return self::MOVE_PREV;
            default:
                return 0;
        }
    }
}
