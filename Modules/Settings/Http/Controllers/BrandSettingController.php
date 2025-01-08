<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Entities\SettingBrandStatement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Entities\BrandSettingEmail;
use Modules\Settings\Entities\BrandSettingEmailImage;
use Modules\Settings\Entities\BrandSettingLogo;
use Modules\Settings\Entities\EmailBodyContent;

class BrandSettingController extends Controller
{
    public function getSettingBrandStatement()
    {
        try {

            $companyId = Auth::guard('api')->user()->company_id;

            $settingBrandStatement = SettingBrandStatement::where('company_id', $companyId)->first();

            if (!$settingBrandStatement) {
                return response()->json([
                    'status' => false,
                    'message' => 'SettingBrandStatement not found',
                    'data' => [],
                ], 404);
            }


            $brandLogo = BrandSettingLogo::where('company_id', $companyId)->first();


            $responseData = [
                'status' => true,
                'message' => 'SettingBrandStatement retrieved successfully',
                'data' => $settingBrandStatement,
                'brand_logo' => $brandLogo,
            ];

            return response()->json($responseData, 200);
        } catch (\Throwable $th) {
            // Log the error
            Log::error('Error in getSettingBrandStatement method: ' . $th->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }




    public function createOrUpdateSettingBrandStatement(Request $request)
    {
        // try {

        //     $customMessages = [
        //         // Add custom validation messages as needed
        //     ];


        //     $validator = Validator::make($request->all(), [
        //         'header_height_by_millimeter' => 'nullable|integer',
        //         'hide_report_header' => 'nullable|boolean',
        //         'is_hard_copy' => 'nullable|boolean',
        //         'is_logo_include_address' => 'nullable|boolean',
        //         'is_logo_include_name' => 'nullable|boolean',
        //         'logo_maximum_height' => 'nullable|integer',
        //         'logo_position' => 'nullable|string',
        //         'logo_width' => 'nullable|string',
        //         'primary_colour' => 'nullable|string',
        //         'print_address_next_to_logo' => 'nullable|boolean',
        //         'print_name_next_to_logo' => 'nullable|boolean',
        //         'secondary_colour' => 'nullable|string',
        //         'show_report_header' => 'nullable|boolean',
        //         'third_colour' => 'nullable|string',

        //     ], $customMessages);

        //     if ($validator->fails()) {
        //         return response()->json([
        //             'status' => false,
        //             'error' => $validator->errors(),
        //             'message' => 'Validation failed',
        //             'data' => [],
        //         ], 400);
        //     }

        //     $companyId = Auth::guard('api')->user()->company_id;


        //     $settingBrandStatement = SettingBrandStatement::updateOrCreate(
        //         ['company_id' => $companyId],
        //         $request->all()
        //     );

        //     Log::info('SettingBrandStatement created/updated successfully.');

        //     return response()->json([
        //         'status' => true,
        //         'message' => 'SettingBrandStatement created/updated successfully',
        //         'data' => $settingBrandStatement,
        //     ], 200);
        // } catch (\Throwable $th) {

        //     Log::error('Error in createOrUpdateSettingBrandStatement method: ' . $th->getMessage());

        //     return response()->json([
        //         'status' => false,
        //         'error' => 'An error occurred',
        //         'message' => $th->getMessage(),
        //         'data' => [],
        //     ], 500);
        // }
        try {
            // return "hello";
            $imageValidator = 0;
            if ($request->hasFile('brand_image')) {
                $imageValidator = Validator::make($request->all(), [
                    // 'brand_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048',
                ]);
            }

            // $imageValidator = Validator::make($request->all(), [
            //     'brand_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048',
            // ]);

            $settingValidator = Validator::make($request->all(), [
                'header_height_by_millimeter' => 'nullable|integer',
                'hide_report_header' => 'nullable|boolean',
                'is_hard_copy' => 'nullable|boolean',
                'is_logo_include_address' => 'nullable|boolean',
                'is_logo_include_name' => 'nullable|boolean',
                'logo_maximum_height' => 'nullable|integer',
                'logo_position' => 'nullable|string',
                'logo_width' => 'nullable|string',
                'primary_colour' => 'nullable|string',
                'print_address_next_to_logo' => 'nullable|boolean',
                'print_name_next_to_logo' => 'nullable|boolean',
                'secondary_colour' => 'nullable|string',
                'show_report_header' => 'nullable|boolean',
                'third_colour' => 'nullable|string',
            ]);


            // if ($imageValidator->fails() || $settingValidator->fails()) {
            //     return response()->json([
            //         'status' => false,
            //         'error' => [
            //             // 'brand_image' => $imageValidator->errors(),
            //             'setting' => $settingValidator->errors(),
            //         ],
            //         'message' => 'Validation failed',
            //         'data' => [],
            //     ], 400);
            // }
            // $brandLogo = null;

            // if ($request->hasFile('brand_image')) {
            //     return "hwllo";

            //     $file = $request->file('brand_image');
            //     $filename = $file->getClientOriginalName();
            //     $fileSize = $file->getSize();


            //     $path = config('app.asset_s') . '/Image';
            //     $filename_s3 = Storage::disk('s3')->put($path, $file);


            //     $brandLogo = BrandSettingLogo::updateOrCreate(
            //         ['company_id' => Auth::guard('api')->user()->company_id],
            //         [
            //             'brand_image' =>  $filename_s3,
            //             'image_name' => $filename,
            //             'file_size' => $fileSize,
            //         ]
            //     );
            // }

            // return "hello";

            $companyId = Auth::guard('api')->user()->company_id;
            $settingBrandStatement = SettingBrandStatement::updateOrCreate(
                ['company_id' => $companyId],
                $request->all()
            );



            return response()->json([
                'status' => true,
                'message' => 'Brand logo and SettingBrandStatement created/updated successfully',
                'data' => [
                    // 'brand_logo' => $brandLogo,
                    'setting_brand_statement' => $settingBrandStatement,
                ],
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error in uploadBrandLogoAndSetting method: ' . $th->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    // public function uploadBrandLogo(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'brand_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048', // Adjust the validation rules as needed

    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => false,
    //                 'error' => $validator->errors(),
    //                 'message' => 'Validation failed',
    //                 'data' => [],
    //             ], 422);
    //         }

    //         // Process the uploaded image
    //         $file = $request->file('brand_image');
    //         $filename = $file->getClientOriginalName();
    //         $fileSize = $file->getSize();

    //         // Upload the image to the desired storage location (e.g., AWS S3)
    //         $path = 'brand_logos/' . $filename;
    //         Storage::disk('s3')->put($path, file_get_contents($file));

    //         // Create or update the brand logo record
    //         $brandLogo = BrandSettingLogo::updateOrCreate(
    //             ['company_id' => Auth::guard('api')->user()->company_id],
    //             [
    //                 'brand_image' => Storage::disk('s3')->put($path, $file),
    //                 'image_name' => $filename,
    //                 'file_size' => $fileSize,
    //             ]
    //         );

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Brand logo uploaded successfully',
    //             'data' => $brandLogo,
    //         ], 200);
    //     } catch (\Exception $ex) {
    //         return response()->json([
    //             'status' => false,
    //             'error' => ['error'],
    //             'message' => $ex->getMessage(),
    //             'data' => [],
    //         ], 500);
    //     }
    // }
    public function uploadBrandLogo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // 'brand_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048', // Adjust the validation rules as needed
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 422);
            }
            $brandLogo = null;
            if ($request->hasFile('brand_image')) {

                $file = $request->file('brand_image');
                $filename = $file->getClientOriginalName();
                $fileSize = $file->getSize();


                $path = config('app.asset_s') . '/Image';
                $filename_s3 = Storage::disk('s3')->put($path, $file);



                $brandLogo = BrandSettingLogo::updateOrCreate(
                    ['company_id' => Auth::guard('api')->user()->company_id],
                    [
                        'brand_image' => $filename_s3,
                        'image_name' => $filename,
                        'file_size' => $fileSize,
                    ]
                );
            }



            return response()->json([
                'status' => true,
                'message' => 'Brand logo uploaded successfully',
                'brand_logo' => $brandLogo,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'error' => ['error'],
                'message' => $ex->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function deleteBrandLogo()
    {
        try {

            $companyId = Auth::guard('api')->user()->company_id;
            DB::transaction(function () use ($companyId) {
                BrandSettingLogo::where('company_id', $companyId)->delete();
            });

            return response()->json([
                'message' => 'Brand logo deleted successfully',
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'error' => ['error'],
                'message' => $ex->getMessage(),
                'data' => [],
            ], 500);
        }
    }


    public function getEmailSettingsWithImage()
    {
        try {
            $companyId = Auth::guard('api')->user()->company_id;

            // Retrieve email settings
            $emailSettings = BrandSettingEmail::where('company_id', $companyId)->first();

            if (!$emailSettings) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email settings not found',
                    'data' => [],
                ], 404);
            }

            // Retrieve associated email image
            $headerImage = BrandSettingEmailImage::where('company_id', $companyId)->where('type', 'header')->first();
            $footerImage = BrandSettingEmailImage::where('company_id', $companyId)->where('type', 'footer')->first();
            $EmailBodyContent = EmailBodyContent::where('company_id', $companyId)->first();

            return response()->json([
                'status' => true,
                'message' => 'Email settings retrieved successfully',
                'data' => [
                    'email_settings' => $emailSettings,
                    'header_image' => $headerImage ?? null,
                    'footer_image' => $footerImage ?? null,
                    'email_body_content' => $EmailBodyContent ?? null
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function createOrUpdateEmailSettings(Request $request)
    {
        try {
            $companyId = Auth::guard('api')->user()->company_id;
            $validationRules = [
                'leftHeaderBtn' => 'nullable|boolean',
                'middletHeaderBtn' => 'nullable|boolean',
                'rightHeaderBtn' => 'nullable|boolean',
                'leftHeaderTextBtn' => 'nullable|boolean',
                'middleHeaderTextBtn' => 'nullable|boolean',
                'rightHeaderTextBtn' => 'nullable|boolean',
                'leftFooterBtn' => 'nullable|boolean',
                'middleFooterBtn' => 'nullable|boolean',
                'rightFooterBtn' => 'nullable|boolean',
                'leftFooterTextBtn' => 'nullable|boolean',
                'middleFooterTextBtn' => 'nullable|boolean',
                'rightFooterTextBtn' => 'nullable|boolean',
                'headerBgColor' => 'nullable|string',
                'footerBgColor' => 'nullable|string',
                'bodyColor' => 'nullable|string',
                'bodyBgColor' => 'nullable|string',
                'height' => 'nullable|string',
                'headerColor' => 'nullable|string',
                'footerColor' => 'nullable|string',
                'selectedFont' => 'nullable|string',
                'selectedFontSize' => 'nullable|integer',
                'type' => 'nullable|string',
                'headerText' => 'nullable|string',
                'footerText' => 'nullable|string',
                'headerImgHeight' => 'nullable|integer',
                'footerImgHeight' => 'nullable|integer',
            ];
            $validator = Validator::make($request->all(), $validationRules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 400);
            }

            // Convert "null" string or empty values to null
            $headerText = $request->input('headerText') === "null" || empty($request->input('headerText')) ? null : $request->input('headerText');
            $footerText = $request->input('footerText') === "null" || empty($request->input('footerText')) ? null : $request->input('footerText');

            $data = [
                'left_header_btn' => $request->input('leftHeaderBtn'),
                'middlet_header_btn' => $request->input('middletHeaderBtn'),
                'right_header_btn' => $request->input('rightHeaderBtn'),
                'left_header_text_btn' => $request->input('leftHeaderTextBtn'),
                'middle_header_text_btn' => $request->input('middleHeaderTextBtn'),
                'right_header_text_btn' => $request->input('rightHeaderTextBtn'),
                'left_footer_btn' => $request->input('leftFooterBtn'),
                'middle_footer_btn' => $request->input('middleFooterBtn'),
                'right_footer_btn' => $request->input('rightFooterBtn'),
                'left_footer_text_btn' => $request->input('leftFooterTextBtn'),
                'middle_footer_text_btn' => $request->input('middleFooterTextBtn'),
                'right_footer_text_btn' => $request->input('rightFooterTextBtn'),
                'header_bg_color' => $request->input('headerBgColor'),
                'footer_bg_color' => $request->input('footerBgColor'),
                'body_color' => $request->input('bodyColor'),
                'body_bg_color' => $request->input('bodyBgColor'),
                'height' => $request->input('height'),
                'header_color' => $request->input('headerColor'),
                'footer_color' => $request->input('footerColor'),
                'header_text' => $headerText, 
                'footer_text' => $footerText,
                'header_img_height' => $request->input('headerImgHeight'),
                'footer_img_height' => $request->input('footerImgHeight'),
                'selected_font' => $request->input('selectedFont'),
                'selected_font_size' => $request->input('selectedFontSize'),
                'company_id' => $companyId,
            ];

            // Handle header and footer image uploads and deletions here...

            $settings = BrandSettingEmail::updateOrCreate(
                ['company_id' => $companyId],
                $data
            );

            return response()->json([
                'status' => true,
                'message' => 'Settings created/updated successfully',
                'data' => [
                    'email_settings' => $settings,
                    'email_image' => [
                        'header_image' => $headerImage ?? null,
                        'footer_image' => $footerImage ?? null
                    ]
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }


    public function deleteHeaderLogo(Request $request)
    {
        try {

            $companyId = Auth::guard('api')->user()->company_id;
            DB::transaction(function () use ($companyId, $request) {
                BrandSettingEmailImage::where('company_id', $companyId)->where('type', $request->type)->delete();
            });

            return response()->json([
                'message' => 'Image deleted successfully',
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'error' => ['error'],
                'message' => $ex->getMessage(),
                'data' => [],
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('settings::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('settings::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('settings::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('settings::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
