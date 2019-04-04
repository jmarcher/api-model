<?php
namespace Tests;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use OUTRIGHTVision\ApiModel;
use OUTRIGHTVision\Exceptions\ImmutableAttributeException;
use OUTRIGHTVision\Relationships\HasMany;
use Orchestra\Testbench\TestCase;
use Tests\Fakes\City;
use Tests\Fakes\Harbor;

class ModelTest extends TestCase
{

    /** @test */
    public function it_should_return_null_for_unexisting_parameters()
    {
        $model = new ApiModel();
        $this->assertNull($model->unexistingParameterXXX);
    }

    /** @test */
    public function it_should_return_valid_value_for_an_existing_parameter()
    {
        $model = new ApiModel(['foo' => 'bar']);
        $this->assertEquals('bar', $model->foo);
    }

    /** @test */
    public function it_should_assign_a_value_to_a_parameter()
    {
        $model = new ApiModel();
        $this->assertNull($model->foo);
        $model->foo = 'bar';
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals(['foo' => 'bar'], $model->toArray());
    }

    /** @test */
    public function it_should_throw_an_error_on_immutble_parameters()
    {
        $model = new class extends ApiModel
        {
            public function getFooAttribute()
            {
                return 'bar';
            }
        };
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals([], $model->toArray());

        $this->expectException(ImmutableAttributeException::class);
        $model->foo = 'baz';
    }

    /** @test */
    public function it_should_retain_data_when_doing_copy_constructor()
    {
        $model = new ApiModel(new ApiModel(['foo' => 'bar']));
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals(['foo' => 'bar'], $model->getData());
    }

    /** @test */
    public function it_should_be_serializable_and_unserializable()
    {
        $model = unserialize(serialize(new ApiModel(['foo' => 'bar'])));
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals(['foo' => 'bar'], $model->getData());
    }

    /** @test */
    public function it_should_be_accessed_as_an_array()
    {
        $model = new ApiModel(['foo' => 'bar']);
        $this->assertEquals('bar', $model['foo']);
        $this->assertTrue(isset($model['foo']));
        $model['foo'] = 'baz';

        $this->assertEquals('baz', $model->foo);

        unset($model['foo']);

        $this->assertNull($model->foo);
        $this->assertFalse($model->has('foo'));
    }

    /** @test */
    public function it_should_return_false_if_the_model_do_not_have_id()
    {
        $model = new ApiModel(['foo' => 'bar']);

        $this->assertFalse($model->exists());
    }

    /** @test */
    public function it_should_return_true_if_the_model_has_an_id()
    {
        $model = new ApiModel(['id' => 111222]);

        $this->assertTrue($model->exists());
    }

    /** @test */
    public function it_should_cast_dates_to_carbon_instances()
    {
        $model = new ApiModel(['created_at' => '2019-01-01 00:00:00']);

        $this->assertInstanceOf(Carbon::class, $model->created_at);
    }

    /** @test */
    public function it_should_cast_single_relationship_to_model()
    {
        $harbor = new Harbor([
            'city' => [
                'data' => [
                    'id'   => 1,
                    'name' => 'Montevideo',
                ],
            ],
        ]);

        $this->assertInstanceOf(City::class, $harbor->city);
    }

    /** @test */
    public function it_should_property_from_relationship_should_be_accessed()
    {
        $harbor = new Harbor([
            'city' => [
                'data' => [
                    'id'   => 1,
                    'name' => 'Montevideo',
                ],
            ],
        ]);

        $this->assertEquals('Montevideo', $harbor->city->name);
        $this->assertTrue($harbor->city->exists());
    }

    /** @test */
    public function it_should_return_a_collection_of_boats()
    {
        $harbor = new Harbor([
            'boats' => [
                'data' => [
                    [
                        'id'   => 1,
                        'name' => 'Queen Mary',
                    ],
                    [
                        'id'   => 2,
                        'name' => 'Reconcho',
                    ],
                ],
            ],
        ]);
        $this->assertInstanceOf(HasMany::class, $harbor->boats);
        $this->assertTrue($harbor->boats->first()->exists());
    }
}
