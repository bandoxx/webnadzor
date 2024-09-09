<?php

namespace App\Service\FileUploader\Types;

use App\Service\FileUploader\BaseUploader;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(lazy: true)]
class MapMarkerIconUploader extends BaseUploader
{}