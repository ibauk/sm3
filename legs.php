<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle claims log reporting
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2022 Bob Stammers
 *
 *
 * This file is part of IBAUK-SCOREMASTER.
 *
 * IBAUK-SCOREMASTER is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License
 *
 * IBAUK-SCOREMASTER is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * MIT License for more details.
 *
 *
 */

  class EntrantLegData {

    public $leg;
    public $ScoreX;
    public $TotalPoints;
    public $RestMinutes;
    public $AvgSpeed;
    public $StartTime;
    public $FinishTime;
    public $CorrectedMiles;
    public $FinishPosition;
    public $OdoRallyStart;
    public $OdoRallyFinish;

    public static function storeLeg($ld,$leg,$entrantRecord) {

        $ld->leg          = $leg;
        $ld->ScoreX       = $entrantRecord['ScoreX'];
        $ld->TotalPoints  = $entrantRecord['TotalPoints'];
        $ld->RestMinutes  = $entrantRecord['RestMinutes'];
        $ld->AvgSpeed     = $entrantRecord['AvgSpeed'];
        $ld->StartTime    = $entrantRecord['StartTime'];
        $ld->FinishTime   = $entrantRecord['FinishTime'];
        $ld->CorrectedMiles = $entrantRecord['CorrectedMiles'];
        $ld->FinishPosition = $entrantRecord['FinishPosition'];
        $ld->OdoRallyStart  = $entrantRecord['OdoRallyStart'];
        $ld->OdoRallyFinish = $entrantRecord['OdoRallyFinish'];

    }

    public static function retrieveLeg($ld,$leg,&$entrantRecord) {

        $entrantRecord['ScoreX']        = $ld->ScoreX;
        $entrantRecord['TotalPoints']   = $ld->TotalPoints;
        $entrantRecord['RestMinutes']   = $ld->RestMinutes;
        $entrantRecord['AvgSpeed']      = $ld->AvgSpeed;
        $entrantRecord['StartTime']     = $ld->StartTime;
        $entrantRecord['FinishTime']    = $ld->FinishTime;
        $entrantRecord['CorrectedMiles'] = $ld->CorrectedMiles;
        $entrantRecord['FinishPosition'] = $ld->FinishPosition;
        $entrantRecord['OdoRallyStart']  = $ld->OdoRallyStart;
        $entrantRecord['OdoRallyFinish'] = $ld->OdoRallyFinish;
        
    }

 }

 class RallyLegData {

    public $leg;
    public $StartTime;
    public $FinishTime;
    public $MaxHours;
    public $MinMiles;
    public $PenaltyMaxMiles;
    public $MaxMilesMethod;
    public $MaxMilesPoints;
    public $PenaltyMilesDNF;
    public $MinPoints;

    
    public static function storeLeg($ld,$leg,$rallyParamsRecord) {

        $ld->leg          = $leg;
        $ld->MaxHours       = $rallyParamsRecord['MaxHours'];
        $ld->MinMiles  = $rallyParamsRecord['MinMiles'];
        $ld->PenaltyMaxMiles  = $rallyParamsRecord['PenaltyMaxMiles'];
        $ld->MaxMilesMethod     = $rallyParamsRecord['MaxMilesMethod'];
        $ld->StartTime    = $rallyParamsRecord['StartTime'];
        $ld->FinishTime   = $rallyParamsRecord['FinishTime'];
        $ld->MaxMilesPoints = $rallyParamsRecord['MaxMilesPoints'];
        $ld->PenaltyMilesDNF = $rallyParamsRecord['PenaltyMilesDNF'];
        $ld->MinPoints  = $rallyParamsRecord['MinPoints'];

    }

    public static function retrieveLeg($ld,$leg,&$rallyParamsRecord) {

        $rallyParamsRecord['MaxHours']        = $ld->MaxHours;
        $rallyParamsRecord['MinMiles']   = $ld->MinMiles;
        $rallyParamsRecord['PenaltyMaxMiles']   = $ld->PenaltyMaxMiles;
        $rallyParamsRecord['MaxMilesMethod']      = $ld->MaxMilesMethod;
        $rallyParamsRecord['StartTime']     = $ld->StartTime;
        $rallyParamsRecord['FinishTime']    = $ld->FinishTime;
        $rallyParamsRecord['MaxMilesPoints'] = $ld->MaxMilesPoints;
        $rallyParamsRecord['PenaltyMilesDNF'] = $ld->PenaltyMilesDNF;
        $rallyParamsRecord['MinPoints']  = $ld->MinPoints;
        
    }



 }
?>
