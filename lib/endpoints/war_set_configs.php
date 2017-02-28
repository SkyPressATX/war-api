<?php

class war_set_configs {

    public function war_app_config( $data ){
        // require_once __DIR__ . "/../config/war_config.php";
        $war_config_object = new war_config;
        $result = [
            'app_config' => $war_config_object->run_static_config( $data->war_config ),
            'sql_tables' => $this->war_build_tables( $data )
        ];
        return $result;
    }

    public function war_build_tables( $data ){
        $data_models = apply_filters( 'war_list_custom_models', array() );
        // require_once __DIR__ . "/../data/war_data.php";
        $war_data = new war_data;
        $result = array_map( function( $model ) use ( $war_data ){
            return [ $model->uri => $war_data->data_create_table( $model->uri, $model->options->args ) ];
        }, $data_models );
        return $result;
    }

}
