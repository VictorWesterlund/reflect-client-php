<?php

	namespace Reflect;

	// Allowed HTTP verbs
	enum Method {
		case GET;
		case POST;
		case PUT;
		case DELETE;
		case PATCH;
		case OPTIONS;
	}