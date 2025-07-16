<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace App\Http\Controllers;

use App\Core\License\LicenseCache;
use App\Models\Account\Customer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFacade;
use L5Swagger\ConfigFactory;
use L5Swagger\Exceptions\L5SwaggerException;

class ApiController extends Controller
{
    /**
     * @OA\PathItem(path="/api")
     *
     * @OA\Info(
     *      version="1.0.2",
     *      title="CLIENTXCMS New Gen API",
     *      description="This is the API documentation for the CLIENTXCMS New Gen API",
     *
     *      @OA\Contact(
     *          email="contact@clientxcms.com"
     *      ),
     * )
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST,
     *      description="Endpoint"
     * )
     *
     * @OA\Tag(
     *     name="Client API",
     *     description="API Endpoints for customer account"
     * )
     * @OA\Tag(
     *     name="Products",
     *     description="API Endpoints for store products"
     * )
     * @OA\Tag(
     *     name="Groups",
     *     description="API Endpoints for store groups"
     * )
     * @OA\Tag(
     *     name="Core",
     *     description="API Endpoints for core"
     * )
     * @OA\Tag(
     *     name="Services",
     *     description="API Endpoints for services"
     * )
     * @OA\Tag(
     *     name="Customers",
     *     description="API Endpoints for customers"
     * )
     */
    public function __construct() {}

    /**
     * @OA\Get(
     *      path="/application/health",
     *      operationId="heatlth",
     *      tags={"Core"},
     *      summary="get heath status",
     *      description="Return health status",
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     * )
     */
    public function health()
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * @OA\Get(
     *      path="/application/license",
     *      operationId="License",
     *      tags={"Core"},
     *      summary="get license",
     *      description="Return license",
     *
     *      @OA\Response(
     *          response=403,
     *          description="Key is invalid"
     *      ),
     * )
     */
    public function license()
    {
        $license = LicenseCache::get();
        if ($license == null) {
            return response()->json(['status' => 'error', 'message' => 'License not found']);
        }

        return response()->json(['status' => 'ok', 'license' => $license->__serialize()]);
    }

    public function apiDocs(Request $request)
    {
        $fileSystem = new Filesystem;

        $configFactory = new ConfigFactory;
        $documentation = 'application';
        $config = $configFactory->documentationConfig($documentation);
        $targetFile = $config['paths']['docs_json'] ?? 'api-docs.json';
        $yaml = false;

        $filePath = $config['paths']['docs'].'/'.$targetFile;

        if (! $fileSystem->exists($filePath)) {
            abort(404, sprintf('Unable to locate documentation file at: "%s"', $filePath));
        }

        $content = $fileSystem->get($filePath);

        $json = json_decode($content, true);

        if ($json === null) {
            abort(404, sprintf('Unable to parse documentation file at: "%s"', $filePath));
        }
        $json['servers'] = [
            [
                'url' => $request->schemeAndHttpHost().'/api',
                'description' => 'Endpoint',
            ],
        ];
        $content = json_encode($json, JSON_PRETTY_PRINT);

        if ($yaml) {
            return ResponseFacade::make($content, 200, [
                'Content-Type' => 'application/yaml',
                'Content-Disposition' => 'inline',
            ]);
        }

        return ResponseFacade::make($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function apiAsset(Request $request)
    {
        $fileSystem = new Filesystem;
        $asset = $request->offsetGet('asset');
        $configFactory = new ConfigFactory;
        $documentation = 'application';
        try {
            $path = swagger_ui_dist_path($documentation, $asset);

            return (new Response(
                $fileSystem->get($path),
                200,
                [
                    'Content-Type' => pathinfo($asset)['extension'] == 'css'
                        ? 'text/css'
                        : 'application/javascript',
                ]
            ))->setSharedMaxAge(31536000)
                ->setMaxAge(31536000)
                ->setExpires(new \DateTime('+1 year'));
        } catch (L5SwaggerException $exception) {
            return abort(404, $exception->getMessage());
        }
    }
}
