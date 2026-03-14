/**
 * @framework   iResponse Services 
 * @version     1.1
 * @author      Amine Idrissi <contact@iresponse.tech>
 * @date        2019
 * @name        $p_model.java	
 */
package tech.iresponse.entities.$p_schema;

import lombok.Data;
import java.io.Serializable;
import lombok.EqualsAndHashCode;
import tech.iresponse.orm.Column;
import tech.iresponse.orm.ActiveRecord;$p_date_import
import com.fasterxml.jackson.annotation.JsonInclude;
import com.fasterxml.jackson.annotation.JsonInclude.Include;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

@JsonInclude(Include.NON_EMPTY)
@EqualsAndHashCode(callSuper = false)
@JsonIgnoreProperties(value = {"columns","database","schema","table","primary"})
@Data public class $p_model extends ActiveRecord implements Serializable
{   $p_colums
    /**
     * @name $p_model
     * @description constructor
     * @access public 
     * @throws java.lang.Exception
     */
    public $p_model() throws Exception 
    { 
        super(); 
        this.setDatabase("$p_database");
        this.setSchema("$p_schema");
        this.setTable("$p_table");
    }

    /**
     * @name $p_model
     * @description constructor
     * @access public 
     * @param primaryValue Object
     * @throws java.lang.Exception
     */
    public $p_model(Object primaryValue) throws Exception
    {
        super(primaryValue);
        this.setDatabase("$p_database");
        this.setSchema("$p_schema");
        this.setTable("$p_table");
        this.load();
    }
}