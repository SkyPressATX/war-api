<?php

class war_set_configs {

    public function war_app_config( $data ){
        $war_config_object = new war_config;
        $result = [
            'app_config' => $war_config_object->run_static_config( $data->war_config ),
            'sql_tables' => $this->war_build_tables( $data )
        ];
        return $result;
    }

    public function war_build_tables( $data ){
        $data_models = apply_filters( 'war_list_custom_models', array() );
        $war_data = new war_data;
        $result = array_map( function( $model ) use ( $war_data ){
            return [ $model->uri => $war_data->data_create_table( $model->uri, $model->options->args ) ];
        }, $data_models );
        return $result;
    }

}
