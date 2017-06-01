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
        $data_models = apply_filters( 'war_data_models', array() );
        $result = array_map( function( $model ){
            $dao_create = new war_dao_create( $model );
            return $dao_create->name;
        }, $data_models );
        return $result;
    }

}