<?php

namespace App\Http\Controllers\Admin;

use App\VendorPage;
use App\Helper\Files;
use Illuminate\Http\Request;
use Froiden\Envato\Helpers\Reply;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\VendorPage\UpdateVendorPageRequest;
use Illuminate\Support\Facades\File;

class VendorPageController extends AdminBaseController
{

    public function update(UpdateVendorPageRequest $request, $id)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('manage_settings'), 403);

        $vendorPage = VendorPage::findOrFail($id);
        $vendorPage->address = $request->address;
        $vendorPage->description = $request->description;
        $vendorPage->primary_contact = $request->primary_contact;
        $vendorPage->secondary_contact = $request->secondary_contact;
        $vendorPage->seo_description = $request->seo_description;
        $vendorPage->seo_keywords = $request->seo_keywords;
        $request->map_option ? $vendorPage->map_option = $request->map_option : $vendorPage->map_option = 'deactive';
        $vendorPage->latitude = $request->latitude ? $request->latitude : 0;
        $vendorPage->longitude = $request->longitude ? $request->longitude : 0;
        $vendorPage->og_image = $request->hasFile('og_image') ? Files::upload($request->og_image, 'vendor-page') : $vendorPage->og_image;
        $vendorPage->save();

        return Reply::dataOnly(['defaultImage' => $request->default_image ?? 0]);
    }

    public function updateImages(Request $request)
    {
        $vendor_page = VendorPage::where('id', $request->vendor_page_id)->first();
        $vendor_page_images_arr = [];
        $default_image_index = 0;

        if ($request->hasFile('file')) {
            $path = base_path('public/user-uploads/' . 'vendor-page');


            if (!File::isDirectory($path)) {
                File::makeDirectory($path);
            }

            $path1 = base_path('public/user-uploads/vendor-page/' . $vendor_page->id);

            if (!File::isDirectory($path1)) {
                File::makeDirectory($path1);
            }


            if ($request->file[0]->getClientOriginalName() !== 'blob') {

                foreach ($request->file as $fileData) {
                    array_push($vendor_page_images_arr, Files::upload($fileData, 'vendor-page/' . $vendor_page->id));

                    if ($fileData->getClientOriginalName() == $request->default_image) {
                        $default_image_index = array_key_last($vendor_page_images_arr);
                    }
                }

            }

            if ($request->uploaded_files) {
                $files = json_decode($request->uploaded_files, true);

                foreach ($files as $file) {
                    array_push($vendor_page_images_arr, $file['name']);

                    if ($file['name'] == $request->default_image) {
                        $default_image_index = array_key_last($vendor_page_images_arr);
                    }
                }

                $arr_diff = array_diff($vendor_page->photos, $vendor_page_images_arr);

                if (count($arr_diff) > 0) {

                    foreach ($arr_diff as $file) {
                        Files::deleteFile($file, 'vendor-page/' . $vendor_page->id);
                    }
                }

            } else {
                if (!is_null($vendor_page->photos) && count($vendor_page->photos) > 0) {
                    Files::deleteFile($vendor_page->photos[0], 'vendor-page/' . $vendor_page->id);
                }
            }
        }

        $vendor_page->photos = json_encode(array_values($vendor_page_images_arr));
        $vendor_page->default_image = count($vendor_page_images_arr) > 0 ? $vendor_page_images_arr[$default_image_index] : null;

        $vendor_page->save();

        return Reply::redirect(route('admin.settings.index').'#vendor_page', __('messages.updatedSuccessfully'));
    }

    // @codingStandardsIgnoreLine
    // @phpstan-ignore-next-line
    public function deleteImage(Request $request)
    {
        $vendor = VendorPage::where('id', $request->vendorId)->first();

        $photo = VendorPage::find($request->vendorId);


        $img = $photo->photos;

        if (($key = array_search($request->file, $img)) !== false) {

            array_splice($img, $key, 1);
            $default_image_index = array_search($request->default_image, $img);
            $vendor->default_image = !empty($img) ? ($default_image_index !== false ? $img[$default_image_index] : $img[0]) : null;
            $vendor->photos = !empty($img) ? json_encode($img) : null;

        }

        $vendor->save();

        return Reply::success(__('File Removed Successfully'));
    }

}
