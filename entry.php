<?php
Header("Content-type: text/html; charset=utf-8");
const O_DOC_ROOT = __DIR__;
require './O/src/EntryPoint.phps';
O_EntryPoint::processRequest();