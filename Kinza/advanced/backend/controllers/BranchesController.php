<?php

namespace backend\controllers;

use Yii;
use backend\models\Branches;
use backend\models\BranchesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\helpers\Json;


/**
 * BranchesController implements the CRUD actions for Branches model.
 */
class BranchesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Branches models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BranchesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if(Yii::$app->request->post('hasEditable')){
            $branchId = Yii::$app->request->post('editableKey');
            $branch = Branches::findOne($branchId);

            $out = Json::encode(['output'=>'','message'=>'']);
            $post = [];
            $posted = current($_POST['Branches']);
            $post['Branches'] = $posted;
            if($branch->load($post)){
                $branch->save();
                $output = 'My Values';
                $out = Json::encode(['output'=>$output,'message'=>'']);
            }
            echo $out;
            return;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Branches model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Branches model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        if( Yii::$app->user->can('create-branch')){
            $model = new Branches();

            if(Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())){

                Yii::$app->response->format = 'json';
                return ActiveForm::validate($model);
            }

            if ($model->load(Yii::$app->request->post())) {
                $model->branch_created_date = date('Y-m-d h:m:s');
                if($model->save()){
                    echo 1;
                } else {
                    echo 0;
                } 
                //return $this->redirect(['view', 'id' => $model->branch_id]);
            } else {
                return $this->renderAjax('create', [
                    'model' => $model,
                ]);
            }
        }
        else {
            throw new ForbiddenHttpException;     
        }
    }

    public function actionImportExcel(){

        $inputFile = 'uploads/branches_file.xlsx';

        try {

            $inputFileType = \PHPExcel_IOFactory::identify($inputFile);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel =$objReader->load($inputFile);

        } catch (Exception $e) {
            die('Error');
        }

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ( $row = 1; $row <= $highestRow; $row++){

            $rowData = $sheet->rangeToArray('A'.$row.':'.$highestColumn.$row,NULL,TRUE,FALSE);

            if($row == 1){
                continue;
            }

            $branch = new Branches();
            $branch_id = $rowData[0][0];
            $branch->compnies_company_id = $rowData[0][1];
            $branch->branch_name = $rowData[0][2];
            $branch->branch_address = $rowData[0][3];
            $branch->branch_created_date = date('Y-m-d H:i:s');
            $branch->branch_status = $rowData[0][4];
            $branch->save();

            print_r($branch->getErrors());
        }
        die('okay');
    }

    /**
     * Updates an existing Branches model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->branch_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Branches model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    public function actionLists($id)
    {
        $countBranches = Branches::find()
            ->where(['compnies_company_id' => $id])
            ->count();

        $branches = Branches::find()
            ->where(['compnies_company_id' => $id])
            ->all();

        if( $countBranches > 0 ){
            foreach ($branches as $branch) {
                echo "<option value='".$branch->branch_id."'>".$branch->branch_name."</option>";
            }
        }
        else {
            echo "<option> - </option>";
        }
    }

    public function actionUpload(){
        $fileName = 'file';
        $uploadPath = '/uploads';

        if (isset($_FILES[$fileName])) {
            $file = \yii\web\UploadedFile::getInstanceByName($fileName);

            //Print file data
            //print_r($file);

            if ($file->saveAs($uploadPath . '/' . $file->name)) {
                //Now save file data to database

                echo \yii\helpers\Json::encode($file);
            }
    } else {
        return $this->render('uploads');
    }

    return false;
    }

    /**
     * Finds the Branches model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Branches the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Branches::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
