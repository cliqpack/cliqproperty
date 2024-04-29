<?php

use App\Models\Company;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateEmailBodyContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_body_contents', function (Blueprint $table) {
            $table->id();
            $table->text('content')->nullable();
            $table->bigInteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->bigInteger('brand_setting_email_id')->unsigned()->nullable();
            $table->foreign('brand_setting_email_id')->references('id')->on('brand_setting_emails')->onDelete('cascade');

            $table->timestamps();
        });
        $companies = Company::all();
        foreach ($companies as $company) {
            $emailContentText = "
            
                    Hi There,

                    This is a preview of a sample email with some text in it. Edit the settings on the right in the Header, Footer and Body and see how they affect the presentation of your emails here.

                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam feugiat nisi et vestibulum efficitur. Aenean pulvinar quam ut diam efficitur, nec congue sem fermentum. Nunc cursus varius purus quis tristique. Nunc finibus non quam ac lobortis. Nunc lacinia magna venenatis augue cursus congue.

                    Pellentesque fermentum quam ut neque malesuada fringilla. In sodales a orci non tincidunt. Aenean ultricies sit amet metus non suscipit. Fusce euismod lacus nec orci tincidunt hendrerit. Phasellus ullamcorper ante mi, nec maximus ante interdum ut. Donec sit amet tristique sem. Ut luctus elit dictum, scelerisque nulla commodo, congue odio.

                    Kind regards

                    Nulla Aenean
                    Phone: 0411 222 333
                    Email: email@mail.com
            ";

            DB::table('email_body_contents')->insert([
                'content' => strtoupper($emailContentText),
                'company_id' => $company->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_body_contents');
    }
}
