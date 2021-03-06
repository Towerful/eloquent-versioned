<?php namespace EloquentVersioned\Tests;

use Illuminate\Database\Eloquent\Model as Eloquent;

class VersionedTest extends FunctionalTestCase
{

    protected $modelPrefix = "\\EloquentVersioned\\Tests\\Models\\";

    public function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
    }

    /**
     * We should be able to create a model
     *
     * @param  array $data
     *
     * @dataProvider createDataProvider
     */
    public function testCreate($data)
    {
        $className = $this->modelPrefix . $data['name'];
        $model = $className::create($data)->fresh();

        // model exists?
        $this->assertInstanceOf($this->modelPrefix . $data['name'], $model);
        $this->assertEquals(1, $model->id);
        $this->assertEquals(1, $model->version);
        $this->assertEquals(1, $model->is_current_version);
    }

    /**
     * Using save() should create a new version
     *
     * @param  array $data
     *
     * @dataProvider createDataProvider
     */
    public function testSave($data)
    {
        $className = $this->modelPrefix . $data['name'];
        $model = $className::create($data)->fresh();

        $model->name = 'Updated ' . $data['name'];
        $model->save();

        // model was updated correctly?
        $this->assertEquals(1, $model->id);
        $this->assertEquals('Updated ' . $data['name'], $model->name);
        $this->assertEquals(2, $model->version);
        $this->assertEquals(1, $model->is_current_version);

        // old model exists?
        $oldModel = $className::onlyOldVersions()->first();
        $this->assertInstanceOf($this->modelPrefix . $data['name'], $oldModel);
        $this->assertEquals(1, $oldModel->version);
        $this->assertEquals(0, $oldModel->is_current_version);

        // one record with scopes applied?
        $models = $className::all();
        $this->assertEquals(1, count($models));

        // two records without scopes applied?
        $models = $className::withOldVersions()->get();
        $this->assertEquals(2, count($models));
    }

    /**
     * Using saveMinor() should not create a new version
     *
     * @param  array $data
     *
     * @dataProvider createDataProvider
     */
    public function testMinorSave($data)
    {
        $className = $this->modelPrefix . $data['name'];
        $model = $className::create($data)->fresh();

        $model->name = 'Updated ' . $data['name'];
        $model->saveMinor();

        // model was updated correctly?
        $this->assertEquals(1, $model->id);
        $this->assertEquals('Updated ' . $data['name'], $model->name);
        $this->assertEquals(1, $model->version);
        $this->assertEquals(1, $model->is_current_version);

        // still only one record?
        $models = $className::all();
        $this->assertEquals(1, count($models));
    }

    /**
     * Provides objects to use by tests
     *
     * @return array
     */
    public function createDataProvider()
    {
        return array(
            array(
                array(
                    'name' => 'Widget',
                    'gadget_id' => 1,
                    'doodad_id' => 1
                )
            ),
            array(
                array(
                    'name' => 'Gadget',
                    'widget_id' => 1,
                    'doodad_id' => 1
                )
            ),
            array(array('name' => 'Doodad', 'widget_id' => 1, 'gadget_id' => 1)),
            [
                [
                    'name' => 'Thingy',
                    'email' => 'test@example.net',
                ],
            ],
        );
    }
}
