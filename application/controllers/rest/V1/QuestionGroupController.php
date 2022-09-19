<?php

/*
* LimeSurvey
* Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

use LimeSurvey\Api\Command\Request\Request;
use LimeSurvey\Api\Command\V1\QuestionGroupList;
use LimeSurvey\Api\Command\V1\QuestionGroupPropertiesGet;

class QuestionGroupController extends LSYii_ControllerRest
{
    /**
     * Survey id prefixed requests
     * 
     * /survey/$surveyId/questiongroup
     * /survey/$surveyId/questiongroup/$id
     *
     * @param [type] $surveyId
     * @param [type] $id
     * @return void
     */
    public function actionIndexGet($surveyId = null, $id = null)
    {
        return $id
            ? $this->actionGetOne($id)
            : $this->actionGetAll($surveyId);
    }

    protected function actionGetAll($surveyId)
    {
        $request = Yii::app()->request;
        $requestData = [
            'sessionKey' => $this->getAuthToken(),
            'surveyID' => $surveyId,
            'language' => $request->getParam('language')
        ];
        $commandResponse = (new QuestionGroupList())
            ->run(new Request($requestData));

        $this->renderCommandResponse($commandResponse);
    }

    protected function actionGetOne($id)
    {
        $request = Yii::app()->request;
        $requestData = [
            'sessionKey' => $this->getAuthToken(),
            'groupID' => $id,
            'language' => $request->getParam('language')
        ];
        $commandResponse = (new QuestionGroupPropertiesGet())
            ->run(new Request($requestData));

        $this->renderCommandResponse($commandResponse);
    }
}
