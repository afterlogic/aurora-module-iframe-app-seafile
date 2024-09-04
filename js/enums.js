'use strict';

var
	_ = require('underscore'),
	
	UserSettings = require('modules/%ModuleName%/js/Settings.js'),
	
	Enums = {}
;

Enums.IframeAppSeafileAuthMode = UserSettings.EAuthMode;

if (typeof window.Enums === 'undefined')
{
	window.Enums = {};
}

_.extendOwn(window.Enums, Enums);
