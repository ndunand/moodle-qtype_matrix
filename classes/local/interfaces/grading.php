<?php

namespace qtype_matrix\local\interfaces;
interface grading {
    public static function create_grade(): grading;

    public static function get_name(): string;

    public static function get_title(): string;
}